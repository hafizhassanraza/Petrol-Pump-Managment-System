<?php

namespace App\Services;

use App\Models\ProductPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProductPriceService
{
    public static function getEffectivePrice(int $productId, ?Carbon $at = null): ?ProductPrice
    {
        $at = $at ?? now();

        return ProductPrice::where('product_id', $productId)
            ->where('effective_from', '<=', $at)
            ->orderByDesc('effective_from')
            ->first();
    }

    public static function getPricePerLiter(int $productId, ?Carbon $at = null): ?float
    {
        $row = self::getEffectivePrice($productId, $at);

        return $row ? (float) $row->price : null;
    }

    public static function setPrice(int $productId, float $price, ?Carbon $effectiveFrom = null, ?int $createdBy = null): ProductPrice
    {
        return ProductPrice::create([
            'product_id' => $productId,
            'price' => round($price, 2),
            'effective_from' => $effectiveFrom ?? now(),
            'created_by' => $createdBy ?? Auth::id() ?? 1,
        ]);
    }
}
