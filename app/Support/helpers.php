<?php

if (! function_exists('money')) {
    /**
     * Format a PKR / money amount without decimals (100.00 → 100).
     */
    function money(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return number_format((float) $amount, 0);
    }
}

if (! function_exists('rate')) {
    /**
     * Format a per-liter / unit rate with 2 decimals (sale rate, purchase rate, etc.).
     */
    function rate(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return number_format((float) $amount, 2);
    }
}

if (! function_exists('report_date')) {
    /**
     * Compact report date: 21/6/26
     */
    function report_date(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        return \Carbon\Carbon::parse($date)->format('j/n/y');
    }
}
