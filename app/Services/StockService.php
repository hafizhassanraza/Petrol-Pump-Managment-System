<?php

namespace App\Services;

use App\Models\Tank;
use RuntimeException;

class StockService
{
    public static function canDecrement(Tank $tank, float $liters): bool
    {
        return (float) $tank->current_stock_liters >= $liters;
    }

    public static function canIncrement(Tank $tank, float $liters): bool
    {
        $capacity = (float) $tank->capacity_liters;

        return ((float) $tank->current_stock_liters + $liters) <= $capacity;
    }

    public static function decrement(Tank $tank, float $liters): void
    {
        if ($liters <= 0) {
            throw new RuntimeException('Liters must be greater than zero.');
        }

        if (! self::canDecrement($tank, $liters)) {
            throw new RuntimeException(
                'Insufficient stock. Available: ' . number_format($tank->current_stock_liters, 2) . ' L'
            );
        }

        $tank->decrement('current_stock_liters', $liters);
    }

    public static function increment(Tank $tank, float $liters): void
    {
        if ($liters <= 0) {
            throw new RuntimeException('Liters must be greater than zero.');
        }

        if (! self::canIncrement($tank, $liters)) {
            throw new RuntimeException(
                'Exceeds tank capacity. Max: ' . number_format($tank->capacity_liters, 2) . ' L'
            );
        }

        $tank->increment('current_stock_liters', $liters);
    }

    public static function reconcile(Tank $tank, float $physicalLiters): void
    {
        $tank->update(['current_stock_liters' => $physicalLiters]);
    }
}
