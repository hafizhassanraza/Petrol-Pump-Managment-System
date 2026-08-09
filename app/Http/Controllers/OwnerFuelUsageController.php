<?php

namespace App\Http\Controllers;

use App\Models\OwnerFuelUsage;
use App\Support\ReportRange;
use Illuminate\Http\Request;

class OwnerFuelUsageController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $usages = OwnerFuelUsage::with(['product', 'nozzle', 'employee', 'employeeShift'])
            ->whereBetween('usage_datetime', [$range['fromAt'], $range['toAt']])
            ->latest('usage_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('owner_fuel_usages.index', array_merge($range, compact('usages')));
    }
}
