<?php

namespace App\Support;

use App\Models\AgencyFuelCredit;
use App\Models\EmployeeShift;
use App\Models\OwnerFuelUsage;
use App\Models\Product;
use App\Models\Tank;
use App\Models\TankRefill;
use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailyFuelMetrics
{
    /**
     * Build purchase/sale rate, profit, and closing stock metrics for each business date.
     *
     * @param  iterable<int, string>  $dates  Y-m-d business dates
     * @return Collection<string, array{
     *     purchase_rate: float|null,
     *     sale_rate: float|null,
     *     profit_per_liter: float|null,
     *     total_profit: float,
     *     closing_stock_liters: float,
     *     closing_balance: float|null
     * }>
     */
    public static function forDates(iterable $dates): Collection
    {
        $dates = collect($dates)->unique()->filter()->sort()->values();

        if ($dates->isEmpty()) {
            return collect();
        }

        $minDate = $dates->first();
        $maxDate = $dates->last();
        [, $maxToAt] = BusinessDayService::businessDayBounds($maxDate);

        $shifts = EmployeeShift::query()
            ->closedBetween($minDate, $maxDate)
            ->whereIn('status', ['submitted', 'verified'])
            ->get(['closed_date', 'total_liters', 'total_amount']);

        $shiftsByDate = $shifts->groupBy(
            fn ($s) => Carbon::parse($s->closed_date)->format('Y-m-d')
        );

        $refills = TankRefill::query()
            ->where('received_datetime', '<=', $maxToAt)
            ->orderBy('received_datetime')
            ->get(['quantity_liters', 'purchase_rate', 'received_datetime']);

        $ownerUsages = OwnerFuelUsage::query()
            ->where('usage_datetime', '<=', $maxToAt)
            ->get(['liters', 'usage_datetime']);

        $agencyCredits = AgencyFuelCredit::query()
            ->where('credit_datetime', '<=', $maxToAt)
            ->get(['liters', 'total_amount', 'credit_datetime']);

        $agencyByDate = $agencyCredits->groupBy(
            fn ($c) => BusinessDayService::toBusinessDate($c->credit_datetime)->toDateString()
        );

        $futureShifts = EmployeeShift::query()
            ->closedAfter($maxDate)
            ->whereIn('status', ['submitted', 'verified'])
            ->get(['total_liters']);
        $futureOwner = OwnerFuelUsage::query()
            ->where('usage_datetime', '>', $maxToAt)
            ->get(['liters']);
        $futureAgency = AgencyFuelCredit::query()
            ->where('credit_datetime', '>', $maxToAt)
            ->get(['liters']);

        $futureRefills = TankRefill::query()
            ->where('received_datetime', '>', $maxToAt)
            ->get(['quantity_liters']);

        $currentTotalStock = (float) Tank::query()->sum('current_stock_liters');

        return $dates->mapWithKeys(function (string $date) use (
            $shiftsByDate,
            $agencyByDate,
            $refills,
            $ownerUsages,
            $agencyCredits,
            $futureShifts,
            $futureOwner,
            $futureAgency,
            $futureRefills,
            $currentTotalStock
        ) {
            [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($date);

            $dayShifts = $shiftsByDate->get($date, collect());
            $dayAgency = $agencyByDate->get($date, collect());
            $liters = (float) $dayShifts->sum('total_liters') + (float) $dayAgency->sum('liters');
            $amount = (float) $dayShifts->sum('total_amount') + (float) $dayAgency->sum('total_amount');

            $saleRate = $liters > 0 ? round($amount / $liters, 2) : null;

            $refillsUntilDay = $refills->filter(
                fn ($r) => Carbon::parse($r->received_datetime)->lte($dayTo)
            );

            $purchaseRate = self::resolvePurchaseRate($refillsUntilDay, $dayFrom, $dayTo);

            $profitPerLiter = ($saleRate !== null && $purchaseRate !== null)
                ? round($saleRate - $purchaseRate, 2)
                : null;

            $totalProfit = ($profitPerLiter !== null && $liters > 0)
                ? round($liters * $profitPerLiter, 2)
                : 0.0;

            $closingStock = self::closingStockLiters(
                $date,
                $dayTo,
                $currentTotalStock,
                $refills,
                $shiftsByDate,
                $ownerUsages,
                $agencyCredits,
                $futureShifts,
                $futureOwner,
                $futureAgency,
                $futureRefills
            );

            $closingBalance = $purchaseRate !== null
                ? round($closingStock * $purchaseRate, 2)
                : null;

            return [
                $date => [
                    'purchase_rate' => $purchaseRate,
                    'sale_rate' => $saleRate,
                    'profit_per_liter' => $profitPerLiter,
                    'total_profit' => $totalProfit,
                    'closing_stock_liters' => round($closingStock, 2),
                    'closing_balance' => $closingBalance,
                ],
            ];
        });
    }

    /**
     * Fixed Petrol / Diesel sales and profit for a date range.
     * Keys: petrol, diesel.
     *
     * @return Collection<string, array{
     *     product_id: int,
     *     product: string,
     *     key: string,
     *     liters: float,
     *     sales_amount: float,
     *     cash: float,
     *     online: float,
     *     purchase_rate: float|null,
     *     sale_rate: float|null,
     *     profit_per_liter: float|null,
     *     total_profit: float,
     *     closing_stock_liters: float,
     *     closing_balance: float|null,
     *     shift_count: int
     * }>
     */
    public static function byProduct(string $from, string $to): Collection
    {
        [, $toAt] = BusinessDayService::businessDayBounds($to);

        $shifts = EmployeeShift::query()
            ->with(['nozzle.product'])
            ->closedBetween($from, $to)
            ->whereIn('status', ['submitted', 'verified'])
            ->get();

        $refills = TankRefill::query()
            ->where('received_datetime', '<=', $toAt)
            ->orderBy('received_datetime')
            ->get(['product_id', 'quantity_liters', 'purchase_rate', 'received_datetime']);

        $futureShifts = EmployeeShift::query()
            ->with('nozzle')
            ->closedAfter($to)
            ->whereIn('status', ['submitted', 'verified'])
            ->get(['nozzle_id', 'total_liters']);
        $futureOwner = OwnerFuelUsage::query()
            ->where('usage_datetime', '>', $toAt)
            ->get(['product_id', 'liters']);

        $futureRefills = TankRefill::query()
            ->where('received_datetime', '>', $toAt)
            ->get(['product_id', 'quantity_liters']);

        $tanks = Tank::query()->get(['id', 'product_id', 'current_stock_liters']);
        $products = FuelProducts::all();

        $rangeFrom = Carbon::parse($from)->setTime(BusinessDayService::SHIFT_START_HOUR, 0, 0);

        return $products->mapWithKeys(function (Product $product) use (
            $shifts,
            $refills,
            $futureShifts,
            $futureOwner,
            $futureRefills,
            $tanks,
            $toAt,
            $rangeFrom
        ) {
            $productId = $product->id;
            $key = FuelProducts::keyFor($product);
            $productShifts = $shifts->filter(
                fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $productId
            );

            $liters = (float) $productShifts->sum('total_liters');
            $amount = (float) $productShifts->sum('total_amount');
            $cash = (float) $productShifts->sum('cash_received');
            $online = (float) $productShifts->sum('online_received');
            $saleRate = $liters > 0 ? round($amount / $liters, 2) : null;

            $productRefills = $refills->filter(fn ($r) => (int) $r->product_id === $productId);
            $purchaseRate = self::resolvePurchaseRate($productRefills, $rangeFrom, $toAt);

            $profitPerLiter = ($saleRate !== null && $purchaseRate !== null)
                ? round($saleRate - $purchaseRate, 2)
                : null;

            $totalProfit = ($profitPerLiter !== null && $liters > 0)
                ? round($liters * $profitPerLiter, 2)
                : 0.0;

            $closingStock = (float) $tanks->where('product_id', $productId)->sum('current_stock_liters');
            $closingStock -= (float) $futureRefills
                ->filter(fn ($r) => (int) $r->product_id === $productId)
                ->sum('quantity_liters');
            $closingStock += (float) $futureShifts
                ->filter(fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $productId)
                ->sum('total_liters');
            $closingStock += (float) $futureOwner
                ->filter(fn ($u) => (int) $u->product_id === $productId)
                ->sum('liters');

            $closingStock = max(0, round($closingStock, 2));
            $closingBalance = $purchaseRate !== null
                ? round($closingStock * $purchaseRate, 2)
                : null;

            return [
                $key => [
                    'product_id' => $productId,
                    'product' => $product->name,
                    'key' => $key,
                    'liters' => round($liters, 2),
                    'sales_amount' => round($amount, 2),
                    'cash' => round($cash, 2),
                    'online' => round($online, 2),
                    'purchase_rate' => $purchaseRate,
                    'sale_rate' => $saleRate,
                    'profit_per_liter' => $profitPerLiter,
                    'total_profit' => $totalProfit,
                    'closing_stock_liters' => $closingStock,
                    'closing_balance' => $closingBalance,
                    'shift_count' => $productShifts->count(),
                ],
            ];
        });
    }

    /**
     * Empty product metrics used in daily stacked cells.
     *
     * @return array{
     *     liters: float,
     *     quantity: float,
     *     sales_amount: float,
     *     cash: float,
     *     online: float,
     *     sale_rate: float|null,
     *     purchase_rate: float|null,
     *     profit_per_liter: float|null,
     *     total_profit: float
     * }
     */
    public static function emptyProductDay(): array
    {
        return [
            'liters' => 0.0,
            'quantity' => 0.0,
            'sales_amount' => 0.0,
            'cash' => 0.0,
            'online' => 0.0,
            'sale_rate' => null,
            'purchase_rate' => null,
            'profit_per_liter' => null,
            'total_profit' => 0.0,
        ];
    }

    /**
     * Per-business-day Petrol / Diesel sales with rates and profit for daily breakdown cells.
     *
     * @return Collection<string, array{petrol: array, diesel: array}>
     */
    public static function dailyByProduct(string $from, string $to): Collection
    {
        [, $toAt] = BusinessDayService::businessDayBounds($to);

        $shifts = EmployeeShift::query()
            ->with(['nozzle.product'])
            ->closedBetween($from, $to)
            ->whereIn('status', ['submitted', 'verified'])
            ->get();

        $refills = TankRefill::query()
            ->where('received_datetime', '<=', $toAt)
            ->orderBy('received_datetime')
            ->get(['product_id', 'quantity_liters', 'purchase_rate', 'received_datetime']);

        $products = FuelProducts::all()->keyBy(fn (Product $p) => FuelProducts::keyFor($p));
        $dates = $shifts
            ->map(fn ($s) => Carbon::parse($s->closed_date)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        return $dates->mapWithKeys(function (string $date) use ($shifts, $refills, $products) {
            [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($date);
            $dayShifts = $shifts->filter(
                fn ($s) => Carbon::parse($s->closed_date)->format('Y-m-d') === $date
            );
            $split = self::splitShiftsByFuel($dayShifts);

            $rows = [];
            foreach (['petrol', 'diesel'] as $key) {
                $product = $products->get($key);
                $base = $split[$key] ?? self::emptyDaySplit();
                $liters = (float) ($base['liters'] ?? 0);
                $amount = (float) ($base['sales_amount'] ?? 0);
                $saleRate = $liters > 0 ? round($amount / $liters, 2) : null;
                $purchaseRate = null;

                if ($product) {
                    $productRefills = $refills->filter(
                        fn ($r) => (int) $r->product_id === (int) $product->id
                            && Carbon::parse($r->received_datetime)->lte($dayTo)
                    );
                    $purchaseRate = self::resolvePurchaseRate($productRefills, $dayFrom, $dayTo);
                }

                $profitPerLiter = ($saleRate !== null && $purchaseRate !== null)
                    ? round($saleRate - $purchaseRate, 2)
                    : null;

                $rows[$key] = [
                    'liters' => $liters,
                    'quantity' => $liters,
                    'sales_amount' => $amount,
                    'cash' => (float) ($base['cash'] ?? 0),
                    'online' => (float) ($base['online'] ?? 0),
                    'sale_rate' => $saleRate,
                    'purchase_rate' => $purchaseRate,
                    'profit_per_liter' => $profitPerLiter,
                    'total_profit' => ($profitPerLiter !== null && $liters > 0)
                        ? round($liters * $profitPerLiter, 2)
                        : 0.0,
                ];
            }

            return [$date => $rows];
        });
    }

    /**
     * Per-business-day opening/closing stock (L) and stock balances (PKR) for Petrol / Diesel.
     *
     * @return Collection<string, array{
     *     petrol: array{stock_opening: float, stock_closing: float, balance_opening: float|null, balance_closing: float|null},
     *     diesel: array{stock_opening: float, stock_closing: float, balance_opening: float|null, balance_closing: float|null},
     *     opening_balance: float,
     *     closing_balance: float
     * }>
     */
    /**
     * @param  Collection<int, string>|null  $extraDates  Extra Y-m-d business dates to include (e.g. refill / price-change days)
     */
    public static function dailyClosingStock(string $from, string $to, ?Collection $extraDates = null): Collection
    {
        $products = FuelProducts::all()->keyBy(fn (Product $p) => FuelProducts::keyFor($p));
        $tanks = Tank::query()->get(['id', 'product_id', 'current_stock_liters']);

        $allRefills = TankRefill::query()
            ->orderBy('received_datetime')
            ->get(['product_id', 'quantity_liters', 'purchase_rate', 'received_datetime']);

        $allShifts = EmployeeShift::query()
            ->with('nozzle')
            ->whereIn('status', ['submitted', 'verified'])
            ->whereNotNull('closed_date')
            ->get(['closed_date', 'nozzle_id', 'total_liters']);

        $allOwner = OwnerFuelUsage::query()
            ->get(['product_id', 'liters', 'usage_datetime']);

        $allAgency = AgencyFuelCredit::query()
            ->get(['product_id', 'liters', 'credit_datetime']);

        $shiftDates = $allShifts
            ->map(fn ($s) => Carbon::parse($s->closed_date)->format('Y-m-d'))
            ->filter(fn ($d) => $d >= $from && $d <= $to);

        $refillDates = $allRefills
            ->map(fn ($r) => BusinessDayService::toBusinessDate($r->received_datetime)->toDateString())
            ->filter(fn ($d) => $d >= $from && $d <= $to);

        $dates = collect($shiftDates->all())
            ->merge($refillDates->all())
            ->merge(($extraDates ?? collect())->all())
            ->filter(fn ($d) => $d >= $from && $d <= $to)
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            $dates = collect([$from]);
        }

        $stockAt = function (int $productId, Carbon $asOf) use ($tanks, $allRefills, $allShifts, $allOwner, $allAgency): float {
            $asOfDate = BusinessDayService::toBusinessDate($asOf)->toDateString();
            $stock = (float) $tanks->where('product_id', $productId)->sum('current_stock_liters');

            $stock -= (float) $allRefills
                ->filter(fn ($r) => (int) $r->product_id === $productId
                    && Carbon::parse($r->received_datetime)->gt($asOf))
                ->sum('quantity_liters');

            $stock += (float) $allShifts
                ->filter(function ($s) use ($productId, $asOfDate) {
                    $shiftDate = Carbon::parse($s->closed_date)->format('Y-m-d');

                    return $shiftDate > $asOfDate
                        && (int) ($s->nozzle->product_id ?? 0) === $productId;
                })
                ->sum('total_liters');

            $stock += (float) $allOwner
                ->filter(fn ($u) => (int) $u->product_id === $productId
                    && Carbon::parse($u->usage_datetime)->gt($asOf))
                ->sum('liters');

            $stock += (float) $allAgency
                ->filter(fn ($c) => (int) $c->product_id === $productId
                    && Carbon::parse($c->credit_datetime)->gt($asOf))
                ->sum('liters');

            return max(0, round($stock, 2));
        };

        return $dates->mapWithKeys(function (string $date) use ($products, $allRefills, $stockAt) {
            [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($date);
            $openingAt = $dayFrom->copy()->subSecond();

            $row = [
                'petrol' => [
                    'stock_opening' => 0.0,
                    'stock_closing' => 0.0,
                    'balance_opening' => null,
                    'balance_closing' => null,
                ],
                'diesel' => [
                    'stock_opening' => 0.0,
                    'stock_closing' => 0.0,
                    'balance_opening' => null,
                    'balance_closing' => null,
                ],
                'opening_balance' => 0.0,
                'closing_balance' => 0.0,
            ];

            $openingTotal = 0.0;
            $closingTotal = 0.0;

            foreach (['petrol', 'diesel'] as $key) {
                $product = $products->get($key);
                if (! $product) {
                    continue;
                }

                $productId = (int) $product->id;
                $openingStock = $stockAt($productId, $openingAt);
                $closingStock = $stockAt($productId, $dayTo);

                $refillsUntilOpen = $allRefills->filter(
                    fn ($r) => (int) $r->product_id === $productId
                        && Carbon::parse($r->received_datetime)->lte($openingAt)
                );
                $refillsUntilClose = $allRefills->filter(
                    fn ($r) => (int) $r->product_id === $productId
                        && Carbon::parse($r->received_datetime)->lte($dayTo)
                );

                $openRate = self::resolvePurchaseRate($refillsUntilOpen, $openingAt->copy()->subDay(), $openingAt);
                $closeRate = self::resolvePurchaseRate($refillsUntilClose, $dayFrom, $dayTo);

                $openBal = $openRate !== null ? round($openingStock * $openRate, 2) : null;
                $closeBal = $closeRate !== null ? round($closingStock * $closeRate, 2) : null;

                $row[$key] = [
                    'stock_opening' => $openingStock,
                    'stock_closing' => $closingStock,
                    'balance_opening' => $openBal,
                    'balance_closing' => $closeBal,
                ];

                $openingTotal += (float) ($openBal ?? 0);
                $closingTotal += (float) ($closeBal ?? 0);
            }

            $row['opening_balance'] = round($openingTotal, 2);
            $row['closing_balance'] = round($closingTotal, 2);

            return [$date => $row];
        });
    }

    /**
     * Empty metrics row used when Petrol/Diesel is missing from a day split.
     *
     * @return array{liters: float, sales_amount: float, cash: float, online: float}
     */
    public static function emptyDaySplit(): array
    {
        return [
            'liters' => 0.0,
            'sales_amount' => 0.0,
            'cash' => 0.0,
            'online' => 0.0,
        ];
    }

    /**
     * Split a collection of shifts into petrol / diesel day totals.
     *
     * @param  Collection<int, EmployeeShift>  $shifts
     * @return array{petrol: array, diesel: array}
     */
    public static function splitShiftsByFuel(Collection $shifts): array
    {
        $ids = FuelProducts::ids();

        $split = [
            'petrol' => self::emptyDaySplit(),
            'diesel' => self::emptyDaySplit(),
        ];

        foreach ($shifts as $shift) {
            $productId = (int) ($shift->nozzle->product_id ?? 0);
            $key = $productId === $ids['petrol'] ? 'petrol' : ($productId === $ids['diesel'] ? 'diesel' : null);
            if ($key === null) {
                continue;
            }

            $split[$key]['liters'] += (float) $shift->total_liters;
            $split[$key]['sales_amount'] += (float) $shift->total_amount;
            $split[$key]['cash'] += (float) $shift->cash_received;
            $split[$key]['online'] += (float) $shift->online_received;
        }

        foreach ($split as $key => $row) {
            $split[$key]['liters'] = round($row['liters'], 2);
            $split[$key]['sales_amount'] = round($row['sales_amount'], 2);
            $split[$key]['cash'] = round($row['cash'], 2);
            $split[$key]['online'] = round($row['online'], 2);
        }

        return $split;
    }

    private static function resolvePurchaseRate(Collection $refillsUntilDay, Carbon $dayFrom, Carbon $dayTo): ?float
    {
        if ($refillsUntilDay->isEmpty()) {
            return null;
        }

        $dayRefills = $refillsUntilDay->filter(function ($r) use ($dayFrom, $dayTo) {
            $at = Carbon::parse($r->received_datetime);

            return $at->betweenIncluded($dayFrom, $dayTo);
        });

        if ($dayRefills->isNotEmpty()) {
            $qty = (float) $dayRefills->sum('quantity_liters');
            if ($qty > 0) {
                $cost = (float) $dayRefills->sum(
                    fn ($r) => (float) $r->quantity_liters * (float) $r->purchase_rate
                );

                return round($cost / $qty, 2);
            }
        }

        $latest = $refillsUntilDay
            ->sortByDesc(fn ($r) => Carbon::parse($r->received_datetime)->timestamp)
            ->first();

        return $latest ? round((float) $latest->purchase_rate, 2) : null;
    }

    private static function closingStockLiters(
        string $date,
        Carbon $dayTo,
        float $currentTotalStock,
        Collection $allRefills,
        Collection $shiftsByDate,
        Collection $ownerUsages,
        Collection $agencyCredits,
        Collection $futureShifts,
        Collection $futureOwner,
        Collection $futureAgency,
        Collection $futureRefills
    ): float {
        $stock = $currentTotalStock;

        $stock -= (float) $allRefills
            ->filter(fn ($r) => Carbon::parse($r->received_datetime)->gt($dayTo))
            ->sum('quantity_liters');

        $stock -= (float) $futureRefills->sum('quantity_liters');

        foreach ($shiftsByDate as $shiftDate => $group) {
            if ($shiftDate > $date) {
                $stock += (float) $group->sum('total_liters');
            }
        }
        $stock += (float) $futureShifts->sum('total_liters');

        $stock += (float) $ownerUsages
            ->filter(fn ($u) => Carbon::parse($u->usage_datetime)->gt($dayTo))
            ->sum('liters');
        $stock += (float) $futureOwner->sum('liters');

        $stock += (float) $agencyCredits
            ->filter(fn ($c) => Carbon::parse($c->credit_datetime)->gt($dayTo))
            ->sum('liters');
        $stock += (float) $futureAgency->sum('liters');

        return max(0, $stock);
    }
}
