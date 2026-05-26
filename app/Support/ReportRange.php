<?php

namespace App\Support;

use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportRange
{
    public static function fromRequest(Request $request): array
    {
        $filter = $request->filter ?? 'today';
        $from = $request->from;
        $to = $request->to;

        if ($filter === 'today') {
            $businessDate = BusinessDayService::currentBusinessDate();
            $from = $businessDate->toDateString();
            $to = $from;
        } elseif ($filter === 'last-week') {
            $from = now()->subDays(7)->format('Y-m-d');
            $to = BusinessDayService::currentBusinessDate()->toDateString();
        } elseif ($filter === 'last-month') {
            $from = now()->subDays(30)->format('Y-m-d');
            $to = BusinessDayService::currentBusinessDate()->toDateString();
        } elseif ($request->from && $request->to) {
            // custom calendar range (by assigned_date / expense_date)
        } else {
            $businessDate = BusinessDayService::currentBusinessDate();
            $from = $businessDate->toDateString();
            $to = $from;
        }

        $fromAt = Carbon::parse($from)->startOfDay();
        $toAt = Carbon::parse($to)->endOfDay();

        // For "today" use exact 9 AM – 9 AM window
        if ($filter === 'today') {
            [$fromAt, $toAt] = BusinessDayService::businessDayBounds($from);
        }

        return compact('filter', 'from', 'to', 'fromAt', 'toAt');
    }
}
