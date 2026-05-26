<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Nozzle;
use App\Models\Shift;
use App\Services\BusinessDayService;
use App\Services\ProductPriceService;
use App\Services\StockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $closing = (float) $request->closing_reading;
        $opening = (float) $shift->opening_reading;

        if ($closing < $opening) {
            return back()->with('error', 'Closing reading cannot be smaller than opening reading.');
        }

        $grossLiters = $closing - $opening;
        $testingLiters = (float) ($request->testing_liters ?? 0);

        if ($testingLiters > $grossLiters) {
            return back()->with('error', 'Testing liters cannot exceed gross liters sold.');
        }

        $netLiters = $grossLiters - $testingLiters;

        if ($netLiters <= 0) {
            return back()->with('error', 'Net liters sold must be greater than zero.');
        }

        $productId = $shift->nozzle->product_id;
        $pricePerLiter = ProductPriceService::getPricePerLiter($productId, now());

        if ($pricePerLiter === null) {
            return back()->with('error', 'No selling price configured for this product. Add a price first.');
        }

        $totalAmount = round($netLiters * $pricePerLiter, 2);
        $cashReceived = (float) $request->cash_received;
        $onlineReceived = (float) $request->online_received;
        $receivedTotal = $cashReceived + $onlineReceived;
        $difference = $receivedTotal - $totalAmount;
        $shortage = $difference < 0 ? round(abs($difference), 2) : 0;
        $extra = $difference > 0 ? round($difference, 2) : 0;

        $tank = $shift->nozzle->tank;
        if (! $tank) {
            return back()->with('error', 'Tank not linked to this nozzle.');
        }

        if (! StockService::canDecrement($tank, $netLiters)) {
            return back()->with(
                'error',
                'Insufficient tank stock. Available: ' . number_format($tank->current_stock_liters, 2) . ' L'
            );
        }

        try {
            DB::transaction(function () use (
                $shift,
                $request,
                $closing,
                $testingLiters,
                $netLiters,
                $pricePerLiter,
                $totalAmount,
                $cashReceived,
                $onlineReceived,
                $shortage,
                $extra,
                $tank
            ) {
                $shift->update([
                    'closing_reading' => $closing,
                    'testing_liters' => $testingLiters,
                    'total_liters' => $netLiters,
                    'price_per_liter' => $pricePerLiter,
                    'total_amount' => $totalAmount,
                    'cash_received' => $cashReceived,
                    'online_received' => $onlineReceived,
                    'shortage_amount' => $shortage,
                    'extra_amount' => $extra,
                    'submitted_at' => now(),
                    'status' => 'submitted',
                ]);

                StockService::decrement($tank, $netLiters);

                $shift->nozzle->update([
                    'current_meter_reading' => $closing,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift closed. Amount: PKR ' . number_format($totalAmount, 2) . ' (' . number_format($netLiters, 2) . ' L)');
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
}
