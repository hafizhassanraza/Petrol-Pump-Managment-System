<?php

namespace App\Http\Controllers;

use App\Models\EmployeeShift;
use App\Models\Employee;
use App\Models\Nozzle;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\Log;

class EmployeeShiftController extends Controller
{
    public function index()
    {
        $shifts = EmployeeShift::with(['employee', 'nozzle', 'shift'])
            ->latest()
            ->get();

        return view('employee_shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('employee_shifts.create', [
            'employees' => Employee::where('status', 1)->get(),
            'nozzles' => Nozzle::where('status', 1)->get(),
            'shifts' => Shift::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'nozzle_id' => 'required',
            'shift_id' => 'required',
            'opening_reading' => 'required|numeric',
        ]);

        $nozzle = Nozzle::find($request->nozzle_id);

        EmployeeShift::create([
            'employee_id' => $request->employee_id,
            'nozzle_id' => $request->nozzle_id,
            'shift_id' => $request->shift_id,
            'assigned_date' => now(),
            'opening_reading' => $request->opening_reading,
            'status' => 'active',
        ]);

        // lock nozzle assignment logic can be added later

        return redirect()->route('employee-shifts.index')
            ->with('success', 'Shift assigned');
    }


    public function closeForm($id)
    {
        $shift = EmployeeShift::with([
            'employee',
            'nozzle.product'
        ])->findOrFail($id);

        if ($shift->status != 'active') {

            return redirect()
                ->route('employee-shifts.index')
                ->with('error', 'Shift already closed.');
        }

        return view('employee_shifts.close', compact('shift'));
    }

    public function close(Request $request, $id)
    {
        Log::info('================ SHIFT CLOSE STARTED ================', [
            'shift_id' => $id,
            'request_data' => $request->all(),
            'time' => now()->toDateTimeString(),
        ]);

        $request->validate([
            'closing_reading' => 'required|numeric|min:0',
            'testing_liters' => 'nullable|numeric|min:0',
            'cash_received' => 'required|numeric|min:0',
            'online_received' => 'required|numeric|min:0',
        ]);

        Log::info('Validation passed');

        $shift = EmployeeShift::with(['nozzle.product', 'nozzle.tank'])
            ->findOrFail($id);

        Log::info('Shift loaded', [
            'shift' => $shift->toArray()
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($shift->status != 'active') {

            Log::warning('Shift already closed', [
                'current_status' => $shift->status
            ]);

            return back()->with('error', 'Shift already closed.');
        }

        if ($request->closing_reading < $shift->opening_reading) {

            Log::warning('Invalid closing reading', [
                'opening_reading' => $shift->opening_reading,
                'closing_reading' => $request->closing_reading,
            ]);

            return back()->with('error', 'Closing reading cannot be smaller than opening reading.');
        }

        Log::info('Business validations passed');


        /*
        |--------------------------------------------------------------------------
        | CALCULATE LITERS
        |--------------------------------------------------------------------------
        */

        $totalLiters = $request->closing_reading - $shift->opening_reading;

        $testingLiters = $request->testing_liters ?? 0;

        $netLiters = $totalLiters - $testingLiters;

        Log::info('Liters calculated', [
            'opening_reading' => $shift->opening_reading,
            'closing_reading' => $request->closing_reading,
            'total_liters' => $totalLiters,
            'testing_liters' => $testingLiters,
            'net_liters' => $netLiters,
        ]);


        /*
        |--------------------------------------------------------------------------
        | GET PRODUCT PRICE
        |--------------------------------------------------------------------------
        */

        $product_id = $shift->nozzle->product_id;

        Log::info('Fetching product price', [
            'product_id' => $product_id
        ]);

        $price = ProductPrice::where('product_id', $product_id)
            ->latest('effective_from')
            ->first();

        if (!$price) {

            Log::error('Product price not found', [
                'product_id' => $product_id
            ]);

            return back()->with('error', 'Product price not found.');
        }

        $pricePerLiter = $price->price;

        Log::info('Product price fetched', [
            'price_per_liter' => $pricePerLiter,
            'effective_from' => $price->effective_from,
        ]);


        /*
        |--------------------------------------------------------------------------
        | TOTAL AMOUNT
        |--------------------------------------------------------------------------
        */

        $totalAmount = $netLiters * $pricePerLiter;

        Log::info('Total amount calculated', [
            'net_liters' => $netLiters,
            'price_per_liter' => $pricePerLiter,
            'total_amount' => $totalAmount,
        ]);


        /*
        |--------------------------------------------------------------------------
        | CASH DIFFERENCE
        |--------------------------------------------------------------------------
        */

        $cashReceived = $request->cash_received;

        $onlineReceived = $request->online_received;

        $difference = ($cashReceived + $onlineReceived) - $totalAmount;

        $shortage = $difference < 0 ? abs($difference) : 0;

        $extra = $difference > 0 ? $difference : 0;

        Log::info('Payment calculations completed', [
            'cash_received' => $cashReceived,
            'online_received' => $onlineReceived,
            'difference' => $difference,
            'shortage' => $shortage,
            'extra' => $extra,
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE SHIFT
        |--------------------------------------------------------------------------
        */

        Log::info('Updating shift record');
        $shift->update([
            'closing_reading' => $request->closing_reading,
            'testing_liters' => $testingLiters,
            'total_liters' => $netLiters,
            'total_amount' => $totalAmount,
            'cash_received' => $cashReceived,
            'online_received' => $onlineReceived,
            'shortage_amount' => $shortage,
            'extra_amount' => $extra,
            'submitted_at' => now(),
            'status' => 'submitted',
            
        ]);

        Log::info('Shift updated successfully');


        /*
        |--------------------------------------------------------------------------
        | REDUCE TANK STOCK
        |--------------------------------------------------------------------------
        */

        $tank = $shift->nozzle->tank;

        if (!$tank) {

            Log::error('Tank not found for nozzle', [
                'nozzle_id' => $shift->nozzle->id
            ]);

            return back()->with('error', 'Tank not found for this nozzle.');
        }

        Log::info('Tank loaded', [
            'tank_id' => $tank->id,
            'current_stock_before' => $tank->current_stock_liters,
        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK AVAILABLE STOCK
        |--------------------------------------------------------------------------
        */

        if ($tank->current_stock_liters < $netLiters) {

            Log::warning('Insufficient tank stock', [
                'available_stock' => $tank->current_stock_liters,
                'required_stock' => $netLiters,
            ]);

            return back()->with('error', 'Insufficient stock in tank.');
        }


        /*
        |--------------------------------------------------------------------------
        | DEDUCT STOCK
        |--------------------------------------------------------------------------
        */

        $tank->decrement('current_stock_liters', $netLiters);

        Log::info('Tank stock deducted', [
            'deducted_liters' => $netLiters,
            'remaining_stock' => $tank->fresh()->current_stock_liters,
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE NOZZLE METER
        |--------------------------------------------------------------------------
        */

        $shift->nozzle->update([
            'current_meter_reading' => $request->closing_reading,
        ]);

        Log::info('Nozzle meter updated', [
            'nozzle_id' => $shift->nozzle->id,
            'new_meter_reading' => $request->closing_reading,
        ]);


        Log::info('================ SHIFT CLOSED SUCCESSFULLY ================', [
            'shift_id' => $shift->id,
            'employee_id' => $shift->employee_id,
            'final_total_amount' => $totalAmount,
            'net_liters' => $netLiters,
        ]);

        return redirect()
            ->route('employee-shifts.index')
            ->with('success', 'Shift closed successfully.');
    }


    // public function close(Request $request, $id)
    // {
    //     $request->validate([
    //         'closing_reading' => 'required|numeric|min:0',
    //         'testing_liters' => 'nullable|numeric|min:0',
    //         'cash_received' => 'required|numeric|min:0',
    //         'online_received' => 'required|numeric|min:0',
    //     ]);


    //     $shift = EmployeeShift::with(['nozzle.product'])->findOrFail($id);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | VALIDATION
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($shift->status != 'active') return back()->with('error','Shift already closed.');
    //     if ($request->closing_reading < $shift->opening_reading) return back()->with('error','Closing reading cannot be smaller than opening reading.');
        


    //     /*
    //     |--------------------------------------------------------------------------
    //     | CALCULATE LITERS
    //     |--------------------------------------------------------------------------
    //     */

    //     $totalLiters = $request->closing_reading - $shift->opening_reading;
    //     $testingLiters = $request->testing_liters ?? 0;
    //     $netLiters = $totalLiters - $testingLiters;


    //     /*
    //     |--------------------------------------------------------------------------
    //     | GET PRODUCT PRICE
    //     |--------------------------------------------------------------------------
    //     */

    //     $product_id = $shift->nozzle->product_id;
    //     $price = ProductPrice::where('product_id', $product_id)->latest('effective_from')->first()
    //     if (!$price)  return back()->with('error','Product price not found.' );
    //     $pricePerLiter = $price->price;


    //     /*
    //     |--------------------------------------------------------------------------
    //     | TOTAL AMOUNT
    //     |--------------------------------------------------------------------------
    //     */

    //     $totalAmount = $netLiters * $pricePerLiter;


    //     /*
    //     |--------------------------------------------------------------------------
    //     | CASH DIFFERENCE
    //     |--------------------------------------------------------------------------
    //     */

    //     $cashReceived = $request->cash_received;
    //     $onlineReceived = $request->online_received;
    //     $difference = ($cashReceived + $onlineReceived) - $totalAmount;
    //     $shortage = $difference < 0? abs($difference): 0;
    //     $extra =$difference > 0? $difference: 0;


    //     /*
    //     |--------------------------------------------------------------------------
    //     | UPDATE SHIFT
    //     |--------------------------------------------------------------------------
    //     */

    //     $shift->update([

    //         'closing_reading' =>$request->closing_reading,
    //         'testing_liters' =>$testingLiters,
    //         'total_liters' =>$netLiters,
    //         'total_amount' =>$totalAmount,
    //         'cash_received' =>$cashReceived,
    //         'shortage_amount' => $shortage,
    //         'extra_amount' => $extra,
    //         'submitted_at' =>now(),
    //         'status' =>'submitted',
    //         /* 'online_received' =>$onlineReceived, */
    //     ]);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | REDUCE TANK STOCK
    //     |--------------------------------------------------------------------------
    //     */

    //     $tank = $shift->nozzle->tank;
    //     if (!$tank) return back()->with('error', 'Tank not found for this nozzle.' );
        


    //     /*
    //     |--------------------------------------------------------------------------
    //     | CHECK AVAILABLE STOCK
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($tank->current_stock_liters < $netLiters)  return back()->with('error','Insufficient stock in tank.');
        
    //     /*
    //     |--------------------------------------------------------------------------
    //     | DEDUCT STOCK
    //     |--------------------------------------------------------------------------
    //     */

    //     $tank->decrement('current_stock_liters',$netLiters);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | UPDATE NOZZLE METER
    //     |--------------------------------------------------------------------------
    //     */

    //     $shift->nozzle->update(['current_meter_reading' =>$request->closing_reading,]);
    //     return redirect()->route('employee-shifts.index') ->with('success','Shift closed successfully.' );
    // }


}