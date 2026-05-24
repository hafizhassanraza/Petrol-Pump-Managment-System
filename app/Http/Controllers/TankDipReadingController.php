<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\TankDipReading;
use Illuminate\Http\Request;

class TankDipReadingController extends Controller
{
    public function index()
    {
        $readings = TankDipReading::with('tank')
            ->latest()
            ->get();

        return view(
            'tank_dip_readings.index',
            compact('readings')
        );
    }


    public function create()
    {
        return view('tank_dip_readings.create', [

            'tanks' =>
                Tank::where('status', 1)->get(),

        ]);
    }


    public function store(Request $request)
    {
        $request->validate([

            'tank_id' =>
                'required',

            'measured_liters' =>
                'required|numeric|min:0',

        ]);


        $tank = Tank::find($request->tank_id);


        /*
        |--------------------------------------------------------------------------
        | SYSTEM STOCK
        |--------------------------------------------------------------------------
        */

        $systemStock =
            $tank->current_stock_liters;


        /*
        |--------------------------------------------------------------------------
        | PHYSICAL STOCK
        |--------------------------------------------------------------------------
        */

        $physicalStock =
            $request->measured_liters;


        /*
        |--------------------------------------------------------------------------
        | DIFFERENCE
        |--------------------------------------------------------------------------
        */

        $difference =
            $physicalStock - $systemStock;


        /*
        |--------------------------------------------------------------------------
        | SAVE READING
        |--------------------------------------------------------------------------
        */

        TankDipReading::create([

            'tank_id' =>
                $request->tank_id,

            'reading_datetime' =>
                now(),

            'measured_liters' =>
                $physicalStock,

            'notes' =>
                'Difference: '.$difference,

            'created_by' =>
                1,
        ]);


        return redirect()
            ->route('tank-dip-readings.index')
            ->with(
                'success',
                'Dip reading added.'
            );
    }
}