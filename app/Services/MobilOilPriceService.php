<?php

namespace App\Services;

use App\Models\MobilOilPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MobilOilPriceService
{
    public static function getEffectivePrice(int $productId, ?Carbon $at = null): ?MobilOilPrice
    {
        $at = $at ?? now();

        return MobilOilPrice::where('mobil_oil_product_id', $productId)
            ->where('effective_from', '<=', $at)
            ->orderByDesc('effective_from')
            ->first();
    }

    public static function getUnitPrice(int $productId, ?Carbon $at = null): ?float
    {
        $row = self::getEffectivePrice($productId, $at);

        return $row ? (float) $row->price : null;
    }

    public static function setPrice(int $productId, float $price, ?Carbon $effectiveFrom = null, ?int $createdBy = null): MobilOilPrice
    {
        return MobilOilPrice::create([
            'mobil_oil_product_id' => $productId,
            'price' => round($price, 2),
            'effective_from' => $effectiveFrom ?? now(),
            'created_by' => $createdBy ?? Auth::id() ?? 1,
        ]);
    }
}
