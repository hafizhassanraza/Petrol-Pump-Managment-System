<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Nozzle;
use App\Services\BusinessDayService;
use App\Services\ProductPriceService;
use App\Services\StockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeShiftController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $shifts = EmployeeShift::with(['employee', 'nozzle.product', 'shift'])
            ->whereBetween('assigned_date', [$range['from'], $range['to']])
            ->latest('assigned_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('employee_shifts.index', array_merge($range, compact('shifts')));
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
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'opening_reading' => 'required|numeric|min:0',
        ]);

        $nozzle = Nozzle::findOrFail($request->nozzle_id);
        $businessDate = BusinessDayService::currentBusinessDate()->toDateString();

        if (EmployeeShift::where('nozzle_id', $nozzle->id)
            ->where('status', 'active')
            ->where('assigned_date', $businessDate)
            ->exists()) {
            return back()->withInput()->with('error', 'This nozzle already has an active shift for today\'s business day (9 AM – 9 AM).');
        }

        $opening = (float) $request->opening_reading;
        $meter = (float) $nozzle->current_meter_reading;

        if ($opening < $meter) {
            return back()->withInput()->with(
                'error',
                'Opening reading cannot be less than nozzle meter (' . number_format($meter, 2) . ').'
            );
        }

        EmployeeShift::create([
            'employee_id' => $request->employee_id,
            'nozzle_id' => $request->nozzle_id,
            'shift_id' => BusinessDayService::defaultShiftId(),
            'assigned_date' => $businessDate,
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

        $pricePerLiter = ProductPriceService::getPricePerLiter(
            $shift->nozzle->product_id,
            now()
        );

        return view('employee_shifts.edit', [
            'shift' => $shift,
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
            'pricePerLiter' => $pricePerLiter,
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

        $pricePerLiter = ProductPriceService::getPricePerLiter(
            $shift->nozzle->product_id,
            now()
        );

        return view('employee_shifts.close', compact('shift', 'pricePerLiter'));
    }

    public function close(Request $request, $id)
    {
        $request->validate([
            'closing_reading' => 'required|numeric|min:0',
            'testing_liters' => 'nullable|numeric|min:0',
            'cash_received' => 'required|numeric|min:0',
            'online_received' => 'required|numeric|min:0',
        ]);

        $shift = EmployeeShift::with(['nozzle.product', 'nozzle.tank'])->findOrFail($id);

        if ($shift->status !== 'active') {
            return back()->with('error', 'Shift already closed.');
        }

        try {
            $metrics = $this->calculateCloseMetrics(
                (float) $shift->opening_reading,
                (float) $request->closing_reading,
                (float) ($request->testing_liters ?? 0),
                $shift->nozzle->product_id
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $tank = $shift->nozzle->tank;
        if (! $tank) {
            return back()->with('error', 'Tank not linked to this nozzle.');
        }

        if (! StockService::canDecrement($tank, $metrics['total_liters'])) {
            return back()->withInput()->with(
                'error',
                'Insufficient tank stock. Available: ' . number_format($tank->current_stock_liters, 2) . ' L'
            );
        }

        $cashReceived = (float) $request->cash_received;
        $onlineReceived = (float) $request->online_received;
        $payment = $this->calculatePaymentTotals($cashReceived, $onlineReceived, $metrics['total_amount']);

        try {
            DB::transaction(function () use ($shift, $request, $metrics, $payment, $tank) {
                $shift->update(array_merge($metrics, $payment, [
                    'closing_reading' => (float) $request->closing_reading,
                    'testing_liters' => (float) ($request->testing_liters ?? 0),
                    'cash_received' => (float) $request->cash_received,
                    'online_received' => (float) $request->online_received,
                    'submitted_at' => now(),
                    'status' => 'submitted',
                ]));

                StockService::decrement($tank, $metrics['total_liters']);

                $shift->nozzle->update([
                    'current_meter_reading' => (float) $request->closing_reading,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift closed. Amount: PKR ' . money($metrics['total_amount']) . ' (' . number_format($metrics['total_liters'], 2) . ' L)');
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
            'opening_reading' => 'required|numeric|min:0',
        ]);

        $opening = (float) $request->opening_reading;
        $meter = (float) $shift->nozzle->current_meter_reading;

        if ($opening < $meter) {
            return back()->withInput()->with(
                'error',
                'Opening reading cannot be less than nozzle meter (' . number_format($meter, 2) . ').'
            );
        }

        $shift->update([
            'employee_id' => $request->employee_id,
            'opening_reading' => $opening,
        ]);

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Active shift updated successfully.');
    }

    private function updateSubmittedShift(Request $request, EmployeeShift $shift)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'closing_reading' => 'required|numeric|min:0',
            'testing_liters' => 'nullable|numeric|min:0',
            'cash_received' => 'required|numeric|min:0',
            'online_received' => 'required|numeric|min:0',
        ]);

        $nozzle = $shift->nozzle;
        $oldNetLiters = (float) $shift->total_liters;
        $oldClosing = (float) $shift->closing_reading;

        if (abs((float) $nozzle->current_meter_reading - $oldClosing) > 0.01) {
            return back()->withInput()->with(
                'error',
                'Nozzle meter no longer matches this shift closing reading. Cannot recalculate safely.'
            );
        }

        try {
            $metrics = $this->calculateCloseMetrics(
                (float) $shift->opening_reading,
                (float) $request->closing_reading,
                (float) ($request->testing_liters ?? 0),
                $nozzle->product_id
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

        try {
            DB::transaction(function () use ($shift, $request, $metrics, $payment, $tank, $nozzle, $oldNetLiters, $oldClosing) {
                StockService::increment($tank, $oldNetLiters);

                if (abs((float) $nozzle->fresh()->current_meter_reading - $oldClosing) < 0.01) {
                    $nozzle->update(['current_meter_reading' => $shift->opening_reading]);
                }

                $tank->refresh();

                if (! StockService::canDecrement($tank, $metrics['total_liters'])) {
                    throw new RuntimeException(
                        'Insufficient tank stock after recalculation. Available: ' .
                        number_format($tank->current_stock_liters, 2) . ' L'
                    );
                }

                $shift->update(array_merge($metrics, $payment, [
                    'employee_id' => $request->employee_id,
                    'closing_reading' => (float) $request->closing_reading,
                    'testing_liters' => (float) ($request->testing_liters ?? 0),
                    'cash_received' => (float) $request->cash_received,
                    'online_received' => (float) $request->online_received,
                    'submitted_at' => now(),
                    'status' => 'submitted',
                    'verified_by' => null,
                ]));

                StockService::decrement($tank, $metrics['total_liters']);

                $nozzle->update([
                    'current_meter_reading' => (float) $request->closing_reading,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift updated and recalculated. Amount: PKR ' .
                money($metrics['total_amount']) . ' (' . number_format($metrics['total_liters'], 2) . ' L)');
    }

    /**
     * @return array{net_liters: float, price_per_liter: float, total_amount: float}
     */
    private function calculateCloseMetrics(
        float $opening,
        float $closing,
        float $testingLiters,
        int $productId
    ): array {
        if ($closing < $opening) {
            throw new RuntimeException('Closing reading cannot be smaller than opening reading.');
        }

        $grossLiters = $closing - $opening;

        if ($testingLiters > $grossLiters) {
            throw new RuntimeException('Testing liters cannot exceed gross liters sold.');
        }

        $netLiters = $grossLiters - $testingLiters;

        if ($netLiters <= 0) {
            throw new RuntimeException('Net liters sold must be greater than zero.');
        }

        $pricePerLiter = ProductPriceService::getPricePerLiter($productId, now());

        if ($pricePerLiter === null) {
            throw new RuntimeException('No selling price configured for this product. Add a price first.');
        }

        return [
            'total_liters' => $netLiters,
            'price_per_liter' => $pricePerLiter,
            'total_amount' => round($netLiters * $pricePerLiter, 2),
        ];
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
