<?php

namespace App\Support;

use Illuminate\Http\Request;

class ReportRange
{
    public static function fromRequest(Request $request): array
    {
        $filter = $request->filter ?? 'today';
        $from = $request->from;
        $to = $request->to;

        if ($filter === 'today') {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($filter === 'last-week') {
            $from = now()->subDays(7)->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($filter === 'last-month') {
            $from = now()->subDays(30)->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($request->from && $request->to) {
            // custom
        } else {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        return compact('filter', 'from', 'to');
    }
}
