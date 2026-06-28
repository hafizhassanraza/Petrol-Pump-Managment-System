<?php

namespace App\Services;

use App\Models\MobilOilProduct;

class MobilOilStockService
{
    public static function canDecrement(MobilOilProduct $product, float $quantity): bool
    {
        return (float) $product->current_stock_qty >= $quantity;
    }

    public static function decrement(MobilOilProduct $product, float $quantity): void
    {
        if (! self::canDecrement($product, $quantity)) {
            throw new \RuntimeException(
                'Insufficient Mobil Oil stock. Available: ' . number_format((float) $product->current_stock_qty, 2)
            );
        }

        $product->decrement('current_stock_qty', $quantity);
    }

    public static function increment(MobilOilProduct $product, float $quantity): void
    {
        $product->increment('current_stock_qty', $quantity);
    }
}
