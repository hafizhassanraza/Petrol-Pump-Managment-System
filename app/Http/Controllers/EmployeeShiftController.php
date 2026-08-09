<?php

namespace App\Http\Controllers;

use App\Models\AgencyCustomer;
use App\Models\AgencyFuelCredit;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Services\BusinessDayService;
use App\Services\ProductPriceService;
use App\Services\StockService;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeShiftController extends Controller
{
    public function index(Request $request)
    {
        $isOpenView = (! $request->filled('filter') && ! $request->filled('status'))
            || $request->get('status') === 'open'
            || $request->get('filter') === 'open';

        if ($isOpenView) {
            $range = [
                'filter' => 'open',
                'from' => BusinessDayService::currentBusinessDate()->toDateString(),
                'to' => BusinessDayService::currentBusinessDate()->toDateString(),
                'fromAt' => null,
                'toAt' => null,
            ];

            $shifts = EmployeeShift::with(['employee', 'nozzle.product', 'shift'])
                ->where('status', 'active')
                ->latest('assigned_date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();

            return view('employee_shifts.index', array_merge($range, [
                'shifts' => $shifts,
                'statusFilter' => 'open',
            ]));
        }

        $range = ReportRange::fromRequest($request);

        $shifts = EmployeeShift::with(['employee', 'nozzle.product', 'shift'])
            ->closedBetween($range['from'], $range['to'])
            ->latest('closed_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('employee_shifts.index', array_merge($range, [
            'shifts' => $shifts,
            'statusFilter' => 'all',
        ]));
    }

    public function create()
    {
        $businessDate = BusinessDayService::currentBusinessDate();
        $shift = BusinessDayService::defaultShift();

        return view('employee_shifts.create', [
            'employees' => Employee::where('status', 1)->get(),
            'nozzles' => Nozzle::with(['product', 'tank', 'dispenser'])->where('status', 1)->get(),
            'shift' => $shift,
            'businessDate' => $businessDate,
            'defaultOpeningDate' => $businessDate->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'assigned_date' => 'nullable|date',
            'opening_reading' => 'required|numeric|min:0',
        ]);

        $nozzle = Nozzle::findOrFail($request->nozzle_id);
        $assignedDate = Carbon::parse(
            $request->input('assigned_date', BusinessDayService::currentBusinessDate()->toDateString())
        )->toDateString();

        if (EmployeeShift::where('nozzle_id', $nozzle->id)
            ->where('status', 'active')
            ->whereDate('assigned_date', $assignedDate)
            ->exists()) {
            return back()->withInput()->with(
                'error',
                'This nozzle already has an active shift for '.$assignedDate.'.'
            );
        }

        $opening = (float) $request->opening_reading;
        $meter = (float) $nozzle->current_meter_reading;

        if ($opening < $meter) {
            return back()->withInput()->with(
                'error',
                'Opening reading cannot be less than nozzle meter ('.number_format($meter, 2).').'
            );
        }

        EmployeeShift::create([
            'employee_id' => $request->employee_id,
            'nozzle_id' => $request->nozzle_id,
            'shift_id' => BusinessDayService::defaultShiftId(),
            'assigned_date' => $assignedDate,
            'opening_reading' => $opening,
            'status' => 'active',
        ]);

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift assigned successfully.');
    }

    public function edit($id)
    {
        $shift = EmployeeShift::with(['employee', 'nozzle.product', 'nozzle.tank'])
            ->findOrFail($id);

        if ($shift->status === 'verified') {
            return redirect()->route('employee-shifts.index')
                ->with('error', 'Verified shifts cannot be edited.');
        }

        $priceAt = $shift->closed_date
            ? Carbon::parse($shift->closed_date)->endOfDay()
            : now();

        $pricePerLiter = ProductPriceService::getPricePerLiter(
            $shift->nozzle->product_id,
            $priceAt
        );

        return view('employee_shifts.edit', [
            'shift' => $shift->load(['ownerFuelUsage', 'agencyFuelCredit']),
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
            'agencyCustomers' => AgencyCustomer::where('status', 1)->orderBy('name')->get(),
            'pricePerLiter' => $pricePerLiter,
            'defaultClosingDate' => BusinessDayService::currentBusinessDate()->toDateString(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $shift = EmployeeShift::with(['nozzle.product', 'nozzle.tank'])->findOrFail($id);

        if ($shift->status === 'verified') {
            return back()->with('error', 'Verified shifts cannot be edited.');
        }

        if ($shift->status === 'active') {
            return $this->updateActiveShift($request, $shift);
        }

        return $this->updateSubmittedShift($request, $shift);
    }

    public function closeForm($id)
    {
        $shift = EmployeeShift::with(['employee', 'nozzle.product', 'nozzle.tank'])
            ->findOrFail($id);

        if ($shift->status !== 'active') {
            return redirect()->route('employee-shifts.index')
                ->with('error', 'Shift already closed.');
        }

        $defaultClosingDate = BusinessDayService::currentBusinessDate()->toDateString();
        $pricePerLiter = ProductPriceService::getPricePerLiter(
            $shift->nozzle->product_id,
            Carbon::parse($defaultClosingDate)->endOfDay()
        );

        return view('employee_shifts.close', [
            'shift' => $shift,
            'pricePerLiter' => $pricePerLiter,
            'defaultClosingDate' => $defaultClosingDate,
            'agencyCustomers' => AgencyCustomer::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function close(Request $request, $id)
    {
        $request->validate([
            'closed_date' => 'nullable|date',
            'closing_reading' => 'required|numeric|min:0',
            'testing_liters' => 'nullable|numeric|min:0',
            'cash_received' => 'required|numeric|min:0',
            'online_received' => 'required|numeric|min:0',
            'has_owner_fuel' => 'nullable|boolean',
            'owner_fuel_liters' => 'nullable|numeric|min:0.1|required_if:has_owner_fuel,1',
            'owner_person_name' => 'nullable|string|max:255',
            'owner_vehicle_no' => 'nullable|string|max:50',
            'owner_purpose' => 'nullable|string|max:255',
            'owner_notes' => 'nullable|string',
            'has_agency_fuel' => 'nullable|boolean',
            'agency_customer_id' => 'nullable|exists:agency_customers,id|required_if:has_agency_fuel,1',
            'agency_fuel_liters' => 'nullable|numeric|min:0.1|required_if:has_agency_fuel,1',
            'agency_sale_price' => 'nullable|numeric|min:0.01|required_if:has_agency_fuel,1',
            'agency_notes' => 'nullable|string',
        ]);

        $shift = EmployeeShift::with(['nozzle.product', 'nozzle.tank'])->findOrFail($id);

        if ($shift->status !== 'active') {
            return back()->with('error', 'Shift already closed.');
        }

        $closedDate = Carbon::parse(
            $request->input('closed_date', BusinessDayService::currentBusinessDate()->toDateString())
        )->toDateString();
        $assignedDate = $shift->assigned_date->toDateString();

        if ($closedDate < $assignedDate) {
            return back()->withInput()->with('error', 'Closing date cannot be before opening date.');
        }

        $hasOwnerFuel = $request->boolean('has_owner_fuel');
        $ownerLiters = $hasOwnerFuel ? (float) $request->owner_fuel_liters : 0.0;
        $hasAgencyFuel = $request->boolean('has_agency_fuel');
        $agencyLiters = $hasAgencyFuel ? (float) $request->agency_fuel_liters : 0.0;

        try {
            $metrics = $this->calculateCloseMetrics(
                (float) $shift->opening_reading,
                (float) $request->closing_reading,
                (float) ($request->testing_liters ?? 0),
                $shift->nozzle->product_id,
                Carbon::parse($closedDate)->endOfDay(),
                $ownerLiters,
                $agencyLiters
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $tank = $shift->nozzle->tank;
        if (! $tank) {
            return back()->with('error', 'Tank not linked to this nozzle.');
        }

        $stockOutLiters = $metrics['total_liters'] + $ownerLiters + $agencyLiters;

        if (! StockService::canDecrement($tank, $stockOutLiters)) {
            return back()->withInput()->with(
                'error',
                'Insufficient tank stock. Available: '.number_format($tank->current_stock_liters, 2).' L'
            );
        }

        $cashReceived = (float) $request->cash_received;
        $onlineReceived = (float) $request->online_received;
        $payment = $this->calculatePaymentTotals($cashReceived, $onlineReceived, $metrics['total_amount']);

        try {
            DB::transaction(function () use (
                $shift,
                $request,
                $metrics,
                $payment,
                $tank,
                $closedDate,
                $hasOwnerFuel,
                $ownerLiters,
                $hasAgencyFuel,
                $agencyLiters,
                $stockOutLiters
            ) {
                $shift->update(array_merge($metrics, $payment, [
                    'closed_date' => $closedDate,
                    'closing_reading' => (float) $request->closing_reading,
                    'testing_liters' => (float) ($request->testing_liters ?? 0),
                    'cash_received' => (float) $request->cash_received,
                    'online_received' => (float) $request->online_received,
                    'submitted_at' => Carbon::parse($closedDate)->setTime(21, 0),
                    'status' => 'submitted',
                ]));

                StockService::decrement($tank, $stockOutLiters);

                $shift->nozzle->update([
                    'current_meter_reading' => (float) $request->closing_reading,
                ]);

                $this->syncOwnerFuelFromRequest($shift, $request, $metrics, $closedDate, $hasOwnerFuel, $ownerLiters);
                $this->syncAgencyFuelFromRequest($shift, $request, $metrics, $closedDate, $hasAgencyFuel, $agencyLiters);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = 'Shift closed. Amount: PKR '.money($metrics['total_amount']).' ('.number_format($metrics['total_liters'], 2).' L)';
        if ($hasOwnerFuel && $ownerLiters > 0) {
            $message .= '. Owner fuel: '.number_format($ownerLiters, 2).' L';
        }
        if ($hasAgencyFuel && $agencyLiters > 0) {
            $message .= '. Agency credit: '.number_format($agencyLiters, 2).' L';
        }

        return redirect()->route('employee-shifts.index')->with('success', $message);
    }

    public function verify($id)
    {
        $shift = EmployeeShift::findOrFail($id);

        if ($shift->status !== 'submitted') {
            return back()->with('error', 'Only submitted shifts can be verified.');
        }

        $shift->update([
            'status' => 'verified',
            'verified_by' => Auth::id() ?? 1,
        ]);

        return back()->with('success', 'Shift verified successfully.');
    }

    private function updateActiveShift(Request $request, EmployeeShift $shift)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'nullable|date',
            'opening_reading' => 'required|numeric|min:0',
        ]);

        $assignedDate = Carbon::parse(
            $request->input('assigned_date', $shift->assigned_date->toDateString())
        )->toDateString();

        $duplicate = EmployeeShift::where('nozzle_id', $shift->nozzle_id)
            ->where('status', 'active')
            ->whereDate('assigned_date', $assignedDate)
            ->where('id', '!=', $shift->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with(
                'error',
                'This nozzle already has an active shift for '.$assignedDate.'.'
            );
        }

        $opening = (float) $request->opening_reading;
        $meter = (float) $shift->nozzle->current_meter_reading;

        if ($opening < $meter) {
            return back()->withInput()->with(
                'error',
                'Opening reading cannot be less than nozzle meter ('.number_format($meter, 2).').'
            );
        }

        $shift->update([
            'employee_id' => $request->employee_id,
            'assigned_date' => $assignedDate,
            'opening_reading' => $opening,
        ]);

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Active shift updated successfully.');
    }

    private function updateSubmittedShift(Request $request, EmployeeShift $shift)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'nullable|date',
            'closed_date' => 'nullable|date',
            'closing_reading' => 'required|numeric|min:0',
            'testing_liters' => 'nullable|numeric|min:0',
            'cash_received' => 'required|numeric|min:0',
            'online_received' => 'required|numeric|min:0',
            'has_owner_fuel' => 'nullable|boolean',
            'owner_fuel_liters' => 'nullable|numeric|min:0.1|required_if:has_owner_fuel,1',
            'owner_person_name' => 'nullable|string|max:255',
            'owner_vehicle_no' => 'nullable|string|max:50',
            'owner_purpose' => 'nullable|string|max:255',
            'owner_notes' => 'nullable|string',
            'has_agency_fuel' => 'nullable|boolean',
            'agency_customer_id' => 'nullable|exists:agency_customers,id|required_if:has_agency_fuel,1',
            'agency_fuel_liters' => 'nullable|numeric|min:0.1|required_if:has_agency_fuel,1',
            'agency_sale_price' => 'nullable|numeric|min:0.01|required_if:has_agency_fuel,1',
            'agency_notes' => 'nullable|string',
        ]);

        $assignedDate = Carbon::parse(
            $request->input('assigned_date', $shift->assigned_date->toDateString())
        )->toDateString();
        $closedDate = Carbon::parse(
            $request->input(
                'closed_date',
                optional($shift->closed_date)->toDateString()
                    ?? BusinessDayService::currentBusinessDate()->toDateString()
            )
        )->toDateString();

        if ($closedDate < $assignedDate) {
            return back()->withInput()->with('error', 'Closing date cannot be before opening date.');
        }

        $shift->load(['ownerFuelUsage', 'agencyFuelCredit.payments']);
        $nozzle = $shift->nozzle;
        $oldOwnerLiters = $shift->ownerFuelUsage ? (float) $shift->ownerFuelUsage->liters : 0.0;
        $oldAgencyLiters = $shift->agencyFuelCredit ? (float) $shift->agencyFuelCredit->liters : 0.0;
        $oldNetLiters = (float) $shift->total_liters;
        $oldStockOut = $oldNetLiters + $oldOwnerLiters + $oldAgencyLiters;
        $oldClosing = (float) $shift->closing_reading;

        if (abs((float) $nozzle->current_meter_reading - $oldClosing) > 0.01) {
            return back()->withInput()->with(
                'error',
                'Nozzle meter no longer matches this shift closing reading. Cannot recalculate safely.'
            );
        }

        $hasOwnerFuel = $request->boolean('has_owner_fuel');
        $ownerLiters = $hasOwnerFuel ? (float) $request->owner_fuel_liters : 0.0;
        $hasAgencyFuel = $request->boolean('has_agency_fuel');
        $agencyLiters = $hasAgencyFuel ? (float) $request->agency_fuel_liters : 0.0;

        if ($shift->agencyFuelCredit && $shift->agencyFuelCredit->payments->isNotEmpty() && ! $hasAgencyFuel) {
            return back()->withInput()->with(
                'error',
                'Cannot remove agency fuel that already has payments. Clear payments first.'
            );
        }

        if ($shift->agencyFuelCredit && $shift->agencyFuelCredit->payments->isNotEmpty()
            && abs($agencyLiters - $oldAgencyLiters) > 0.009) {
            return back()->withInput()->with(
                'error',
                'Cannot change agency liters after payments were recorded.'
            );
        }

        try {
            $metrics = $this->calculateCloseMetrics(
                (float) $shift->opening_reading,
                (float) $request->closing_reading,
                (float) ($request->testing_liters ?? 0),
                $nozzle->product_id,
                Carbon::parse($closedDate)->endOfDay(),
                $ownerLiters,
                $agencyLiters
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $tank = $nozzle->tank;
        if (! $tank) {
            return back()->with('error', 'Tank not linked to this nozzle.');
        }

        $cashReceived = (float) $request->cash_received;
        $onlineReceived = (float) $request->online_received;
        $payment = $this->calculatePaymentTotals($cashReceived, $onlineReceived, $metrics['total_amount']);
        $newStockOut = $metrics['total_liters'] + $ownerLiters + $agencyLiters;

        try {
            DB::transaction(function () use (
                $shift,
                $request,
                $metrics,
                $payment,
                $tank,
                $nozzle,
                $oldStockOut,
                $oldClosing,
                $assignedDate,
                $closedDate,
                $hasOwnerFuel,
                $ownerLiters,
                $hasAgencyFuel,
                $agencyLiters,
                $newStockOut
            ) {
                StockService::increment($tank, $oldStockOut);

                if (abs((float) $nozzle->fresh()->current_meter_reading - $oldClosing) < 0.01) {
                    $nozzle->update(['current_meter_reading' => $shift->opening_reading]);
                }

                $tank->refresh();

                if (! StockService::canDecrement($tank, $newStockOut)) {
                    throw new RuntimeException(
                        'Insufficient tank stock after recalculation. Available: '.
                        number_format($tank->current_stock_liters, 2).' L'
                    );
                }

                $shift->update(array_merge($metrics, $payment, [
                    'employee_id' => $request->employee_id,
                    'assigned_date' => $assignedDate,
                    'closed_date' => $closedDate,
                    'closing_reading' => (float) $request->closing_reading,
                    'testing_liters' => (float) ($request->testing_liters ?? 0),
                    'cash_received' => (float) $request->cash_received,
                    'online_received' => (float) $request->online_received,
                    'submitted_at' => Carbon::parse($closedDate)->setTime(21, 0),
                    'status' => 'submitted',
                    'verified_by' => null,
                ]));

                StockService::decrement($tank, $newStockOut);

                $nozzle->update([
                    'current_meter_reading' => (float) $request->closing_reading,
                ]);

                $this->syncOwnerFuelFromRequest($shift, $request, $metrics, $closedDate, $hasOwnerFuel, $ownerLiters);
                $this->syncAgencyFuelFromRequest($shift, $request, $metrics, $closedDate, $hasAgencyFuel, $agencyLiters);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift updated and recalculated. Amount: PKR '.
                money($metrics['total_amount']).' ('.number_format($metrics['total_liters'], 2).' L)');
    }

    /**
     * @return array{total_liters: float, price_per_liter: float, total_amount: float}
     */
    private function calculateCloseMetrics(
        float $opening,
        float $closing,
        float $testingLiters,
        int $productId,
        ?Carbon $priceAt = null,
        float $ownerLiters = 0.0,
        float $agencyLiters = 0.0
    ): array {
        if ($closing < $opening) {
            throw new RuntimeException('Closing reading cannot be smaller than opening reading.');
        }

        $grossLiters = $closing - $opening;

        if ($testingLiters > $grossLiters) {
            throw new RuntimeException('Testing liters cannot exceed gross liters sold.');
        }

        if ($ownerLiters < 0 || $agencyLiters < 0) {
            throw new RuntimeException('Owner/agency fuel liters cannot be negative.');
        }

        if (($testingLiters + $ownerLiters + $agencyLiters) > $grossLiters) {
            throw new RuntimeException('Testing + owner + agency liters cannot exceed gross liters.');
        }

        $netLiters = $grossLiters - $testingLiters - $ownerLiters - $agencyLiters;

        if ($netLiters < 0) {
            throw new RuntimeException('Net liters sold cannot be negative.');
        }

        $pricePerLiter = ProductPriceService::getPricePerLiter($productId, $priceAt ?? now());

        if (($netLiters > 0 || $ownerLiters > 0 || $agencyLiters > 0) && $pricePerLiter === null) {
            throw new RuntimeException('No selling price configured for this product. Add a price first.');
        }

        $pricePerLiter = (float) ($pricePerLiter ?? 0);

        return [
            'total_liters' => $netLiters,
            'price_per_liter' => $pricePerLiter,
            'total_amount' => round($netLiters * $pricePerLiter, 2),
        ];
    }

    private function syncOwnerFuelFromRequest(
        EmployeeShift $shift,
        Request $request,
        array $metrics,
        string $closedDate,
        bool $hasOwnerFuel,
        float $ownerLiters
    ): void {
        $existing = $shift->ownerFuelUsage;

        if (! $hasOwnerFuel || $ownerLiters <= 0) {
            $existing?->delete();

            return;
        }

        $payload = [
            'product_id' => $shift->nozzle->product_id,
            'nozzle_id' => $shift->nozzle_id,
            'employee_shift_id' => $shift->id,
            'employee_id' => $shift->employee_id,
            'vehicle_no' => $request->owner_vehicle_no,
            'person_name' => $request->owner_person_name ?: 'Owner',
            'purpose' => $request->owner_purpose,
            'liters' => $ownerLiters,
            'price_per_liter' => $metrics['price_per_liter'],
            'total_amount' => round($ownerLiters * $metrics['price_per_liter'], 2),
            'usage_datetime' => Carbon::parse($closedDate)->setTime(21, 0),
            'notes' => $request->owner_notes,
            'created_by' => Auth::id() ?? 1,
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            OwnerFuelUsage::create($payload);
        }
    }

    private function syncAgencyFuelFromRequest(
        EmployeeShift $shift,
        Request $request,
        array $metrics,
        string $closedDate,
        bool $hasAgencyFuel,
        float $agencyLiters
    ): void {
        $existing = $shift->agencyFuelCredit;

        if (! $hasAgencyFuel || $agencyLiters <= 0) {
            if ($existing && $existing->payments()->exists()) {
                throw new RuntimeException('Cannot remove agency fuel that already has payments.');
            }
            $existing?->delete();

            return;
        }

        $agencyPrice = $request->filled('agency_sale_price')
            ? (float) $request->agency_sale_price
            : (float) $metrics['price_per_liter'];

        if ($agencyPrice <= 0) {
            throw new RuntimeException('Agency sale price is required.');
        }

        $amount = round($agencyLiters * $agencyPrice, 2);
        $payload = [
            'agency_customer_id' => (int) $request->agency_customer_id,
            'employee_shift_id' => $shift->id,
            'nozzle_id' => $shift->nozzle_id,
            'product_id' => $shift->nozzle->product_id,
            'employee_id' => $shift->employee_id,
            'liters' => $agencyLiters,
            'price_per_liter' => $agencyPrice,
            'total_amount' => $amount,
            'credit_datetime' => Carbon::parse($closedDate)->setTime(21, 0),
            'notes' => $request->agency_notes,
            'created_by' => Auth::id() ?? 1,
        ];

        if ($existing) {
            $paid = (float) $existing->paid_amount;
            if ($paid > $amount + 0.009) {
                throw new RuntimeException('Agency credit total cannot be less than already paid amount.');
            }
            $existing->update($payload);
            $existing->refreshPaymentStatus();
        } else {
            AgencyFuelCredit::create(array_merge($payload, [
                'paid_amount' => 0,
                'status' => 'open',
            ]));
        }
    }

    /**
     * @return array{shortage_amount: float, extra_amount: float}
     */
    private function calculatePaymentTotals(float $cashReceived, float $onlineReceived, float $totalAmount): array
    {
        $difference = ($cashReceived + $onlineReceived) - $totalAmount;

        return [
            'shortage_amount' => $difference < 0 ? round(abs($difference), 2) : 0,
            'extra_amount' => $difference > 0 ? round($difference, 2) : 0,
        ];
    }
}
