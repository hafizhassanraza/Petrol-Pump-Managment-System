<?php

namespace App\Support;

use App\Models\CashTransaction;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\MobilOilSale;
use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily physical cash drawer ledger with opening / closing balances.
 *
 * Closing = Opening + Sales Cash + Cash In − Cash Out − Expenses
 * Sales Bank/Online is shown for reference and does not affect drawer balance.
 * Only CashTransaction rows with payment_method = cash affect the drawer.
 * Fuel sales are attributed by shift closed_date.
 */
class DailyCashLedger
{
    /**
     * @return array{
     *     days: Collection<int, array{
     *         date: string,
     *         label: string,
     *         opening: float,
     *         sales_cash: float,
     *         sales_bank: float,
     *         cash_in: float,
     *         cash_out: float,
     *         expenses: float,
     *         closing: float
     *     }>,
     *     opening_balance: float,
     *     closing_balance: float,
     *     total_sales_cash: float,
     *     total_sales_bank: float,
     *     total_cash_in: float,
     *     total_cash_out: float,
     *     total_expenses: float
     * }
     */
    public static function forRange(string $from, string $to): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        [$rangeFromAt] = BusinessDayService::businessDayBounds($from);
        [, $rangeToAt] = BusinessDayService::businessDayBounds($to);

        $shifts = EmployeeShift::query()
            ->whereIn('status', ['submitted', 'verified'])
            ->closedOnOrBefore($to)
            ->get(['closed_date', 'cash_received', 'online_received']);

        $mobilSales = MobilOilSale::query()
            ->where('sold_datetime', '<=', $rangeToAt)
            ->get(['sold_datetime', 'payment_method', 'total_amount']);

        $transactions = CashTransaction::query()
            ->where('payment_method', 'cash')
            ->whereDate('transaction_date', '<=', $to)
            ->get(['type', 'amount', 'transaction_date']);

        $expenses = Expense::query()
            ->whereDate('expense_date', '<=', $to)
            ->get(['amount', 'expense_date']);

        $opening = self::balanceBefore($from, $shifts, $mobilSales, $transactions, $expenses, $rangeFromAt);

        $days = collect();
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $running = $opening;

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($date);

            $salesCash = (float) $shifts
                ->filter(fn ($s) => Carbon::parse($s->closed_date)->toDateString() === $date)
                ->sum('cash_received');
            $salesBank = (float) $shifts
                ->filter(fn ($s) => Carbon::parse($s->closed_date)->toDateString() === $date)
                ->sum('online_received');

            $dayMobil = $mobilSales->filter(
                fn ($s) => Carbon::parse($s->sold_datetime)->betweenIncluded($dayFrom, $dayTo)
            );
            $salesCash += (float) $dayMobil->where('payment_method', 'cash')->sum('total_amount');
            $salesBank += (float) $dayMobil->where('payment_method', 'online')->sum('total_amount');

            $cashIn = (float) $transactions
                ->filter(fn ($t) => $t->type === CashTransaction::TYPE_IN
                    && $t->transaction_date->toDateString() === $date)
                ->sum('amount');

            $cashOut = (float) $transactions
                ->filter(fn ($t) => $t->type === CashTransaction::TYPE_OUT
                    && $t->transaction_date->toDateString() === $date)
                ->sum('amount');

            $dayExpenses = (float) $expenses
                ->filter(fn ($e) => Carbon::parse($e->expense_date)->toDateString() === $date)
                ->sum('amount');

            $closing = round($running + $salesCash + $cashIn - $cashOut - $dayExpenses, 2);

            $days->push([
                'date' => $date,
                'label' => report_date($date),
                'opening' => round($running, 2),
                'sales_cash' => round($salesCash, 2),
                'sales_bank' => round($salesBank, 2),
                'cash_in' => round($cashIn, 2),
                'cash_out' => round($cashOut, 2),
                'expenses' => round($dayExpenses, 2),
                'closing' => $closing,
            ]);

            $running = $closing;
            $cursor->addDay();
        }

        return [
            'days' => $days,
            'opening_balance' => round($opening, 2),
            'closing_balance' => round($running, 2),
            'total_sales_cash' => round((float) $days->sum('sales_cash'), 2),
            'total_sales_bank' => round((float) $days->sum('sales_bank'), 2),
            'total_cash_in' => round((float) $days->sum('cash_in'), 2),
            'total_cash_out' => round((float) $days->sum('cash_out'), 2),
            'total_expenses' => round((float) $days->sum('expenses'), 2),
        ];
    }

    private static function balanceBefore(
        string $from,
        Collection $shifts,
        Collection $mobilSales,
        Collection $transactions,
        Collection $expenses,
        Carbon $rangeFromAt
    ): float {
        $salesCash = (float) $shifts
            ->filter(fn ($s) => Carbon::parse($s->closed_date)->toDateString() < $from)
            ->sum('cash_received');

        $salesCash += (float) $mobilSales
            ->filter(fn ($s) => Carbon::parse($s->sold_datetime)->lt($rangeFromAt)
                && $s->payment_method === 'cash')
            ->sum('total_amount');

        $cashIn = (float) $transactions
            ->filter(fn ($t) => $t->type === CashTransaction::TYPE_IN
                && $t->transaction_date->toDateString() < $from)
            ->sum('amount');

        $cashOut = (float) $transactions
            ->filter(fn ($t) => $t->type === CashTransaction::TYPE_OUT
                && $t->transaction_date->toDateString() < $from)
            ->sum('amount');

        $expenseTotal = (float) $expenses
            ->filter(fn ($e) => Carbon::parse($e->expense_date)->toDateString() < $from)
            ->sum('amount');

        return round($salesCash + $cashIn - $cashOut - $expenseTotal, 2);
    }
}
