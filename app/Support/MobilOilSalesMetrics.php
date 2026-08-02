<?php

namespace App\Support;

use App\Models\MobilOilPurchase;
use App\Models\MobilOilSale;
use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MobilOilSalesMetrics
{
    /**
     * Product-wise Mobil Oil sales & profit for a datetime range.
     *
     * @return Collection<int, array{
     *     product_id: int,
     *     product: string,
     *     unit: string,
     *     quantity: float,
     *     sales_amount: float,
     *     cash: float,
     *     online: float,
     *     purchase_rate: float|null,
     *     sale_rate: float|null,
     *     profit_per_unit: float|null,
     *     total_profit: float,
     *     sale_count: int
     * }>
     */
    public static function byProduct(Carbon|string $fromAt, Carbon|string $toAt): Collection
    {
        $fromAt = Carbon::parse($fromAt);
        $toAt = Carbon::parse($toAt);

        $sales = MobilOilSale::query()
            ->with('product')
            ->whereBetween('sold_datetime', [$fromAt, $toAt])
            ->get();

        if ($sales->isEmpty()) {
            return collect();
        }

        $purchases = MobilOilPurchase::query()
            ->where('received_datetime', '<=', $toAt)
            ->orderBy('received_datetime')
            ->get(['mobil_oil_product_id', 'quantity', 'purchase_rate', 'received_datetime']);

        return $sales
            ->groupBy('mobil_oil_product_id')
            ->map(function (Collection $group) use ($purchases, $fromAt, $toAt) {
                $product = $group->first()->product;
                $productId = (int) $group->first()->mobil_oil_product_id;
                $qty = (float) $group->sum('quantity');
                $amount = (float) $group->sum('total_amount');
                $cash = (float) $group->where('payment_method', 'cash')->sum('total_amount');
                $online = (float) $group->where('payment_method', 'online')->sum('total_amount');
                $saleRate = $qty > 0 ? round($amount / $qty, 2) : null;

                $productPurchases = $purchases->filter(
                    fn ($p) => (int) $p->mobil_oil_product_id === $productId
                );
                $purchaseRate = self::resolvePurchaseRate($productPurchases, $fromAt, $toAt);

                $profitPerUnit = ($saleRate !== null && $purchaseRate !== null)
                    ? round($saleRate - $purchaseRate, 2)
                    : null;

                $totalProfit = ($profitPerUnit !== null && $qty > 0)
                    ? round($qty * $profitPerUnit, 2)
                    : 0.0;

                return [
                    'product_id' => $productId,
                    'product' => $product->name ?? 'Unknown',
                    'unit' => $product->unit ?? '',
                    'quantity' => round($qty, 2),
                    'sales_amount' => round($amount, 2),
                    'cash' => round($cash, 2),
                    'online' => round($online, 2),
                    'purchase_rate' => $purchaseRate,
                    'sale_rate' => $saleRate,
                    'profit_per_unit' => $profitPerUnit,
                    'total_profit' => $totalProfit,
                    'sale_count' => $group->count(),
                ];
            })
            ->sortByDesc('sales_amount')
            ->values();
    }

    /**
     * Per-business-day Mobil Oil totals (all products combined) for daily breakdown cells.
     *
     * @return Collection<string, array{
     *     quantity: float,
     *     liters: float,
     *     sales_amount: float,
     *     cash: float,
     *     online: float,
     *     sale_rate: float|null,
     *     purchase_rate: float|null,
     *     profit_per_liter: float|null,
     *     total_profit: float
     * }>
     */
    public static function dailyTotals(Carbon|string $fromAt, Carbon|string $toAt): Collection
    {
        $fromAt = Carbon::parse($fromAt);
        $toAt = Carbon::parse($toAt);

        $sales = MobilOilSale::query()
            ->whereBetween('sold_datetime', [$fromAt, $toAt])
            ->get();

        if ($sales->isEmpty()) {
            return collect();
        }

        $purchases = MobilOilPurchase::query()
            ->where('received_datetime', '<=', $toAt)
            ->orderBy('received_datetime')
            ->get(['mobil_oil_product_id', 'quantity', 'purchase_rate', 'received_datetime']);

        return $sales
            ->groupBy(fn ($sale) => BusinessDayService::toBusinessDate($sale->sold_datetime)->toDateString())
            ->map(function (Collection $daySales, string $date) use ($purchases) {
                [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($date);
                $qty = (float) $daySales->sum('quantity');
                $amount = (float) $daySales->sum('total_amount');
                $cash = (float) $daySales->where('payment_method', 'cash')->sum('total_amount');
                $online = (float) $daySales->where('payment_method', 'online')->sum('total_amount');
                $saleRate = $qty > 0 ? round($amount / $qty, 2) : null;

                $profit = 0.0;
                $purchaseCost = 0.0;
                $purchaseQty = 0.0;

                foreach ($daySales->groupBy('mobil_oil_product_id') as $productId => $group) {
                    $groupQty = (float) $group->sum('quantity');
                    $groupAmount = (float) $group->sum('total_amount');
                    $groupSaleRate = $groupQty > 0 ? round($groupAmount / $groupQty, 2) : null;
                    $productPurchases = $purchases->filter(
                        fn ($p) => (int) $p->mobil_oil_product_id === (int) $productId
                            && Carbon::parse($p->received_datetime)->lte($dayTo)
                    );
                    $purchaseRate = self::resolvePurchaseRate($productPurchases, $dayFrom, $dayTo);

                    if ($purchaseRate !== null && $groupQty > 0) {
                        $purchaseCost += $purchaseRate * $groupQty;
                        $purchaseQty += $groupQty;
                    }

                    if ($groupSaleRate !== null && $purchaseRate !== null && $groupQty > 0) {
                        $profit += round($groupQty * ($groupSaleRate - $purchaseRate), 2);
                    }
                }

                $purchaseRate = $purchaseQty > 0 ? round($purchaseCost / $purchaseQty, 2) : null;
                $profitPerUnit = ($saleRate !== null && $purchaseRate !== null)
                    ? round($saleRate - $purchaseRate, 2)
                    : null;

                return [
                    'quantity' => round($qty, 2),
                    'liters' => round($qty, 2),
                    'sales_amount' => round($amount, 2),
                    'cash' => round($cash, 2),
                    'online' => round($online, 2),
                    'sale_rate' => $saleRate,
                    'purchase_rate' => $purchaseRate,
                    'profit_per_liter' => $profitPerUnit,
                    'total_profit' => round($profit, 2),
                ];
            });
    }

    private static function resolvePurchaseRate(Collection $purchasesUntil, Carbon $fromAt, Carbon $toAt): ?float
    {
        if ($purchasesUntil->isEmpty()) {
            return null;
        }

        $periodPurchases = $purchasesUntil->filter(function ($p) use ($fromAt, $toAt) {
            $at = Carbon::parse($p->received_datetime);

            return $at->betweenIncluded($fromAt, $toAt);
        });

        if ($periodPurchases->isNotEmpty()) {
            $qty = (float) $periodPurchases->sum('quantity');
            if ($qty > 0) {
                $cost = (float) $periodPurchases->sum(
                    fn ($p) => (float) $p->quantity * (float) $p->purchase_rate
                );

                return round($cost / $qty, 2);
            }
        }

        $latest = $purchasesUntil
            ->sortByDesc(fn ($p) => Carbon::parse($p->received_datetime)->timestamp)
            ->first();

        return $latest ? round((float) $latest->purchase_rate, 2) : null;
    }
}
