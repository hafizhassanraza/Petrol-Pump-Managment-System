<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Fuel station sells exactly two products: Petrol and Diesel.
 */
class FuelProducts
{
    public const PETROL = 'Petrol';

    public const DIESEL = 'Diesel';

    public const NAMES = [self::PETROL, self::DIESEL];

    /**
     * Ensure Petrol and Diesel exist (idempotent). Returns [petrol, diesel] models keyed by name.
     *
     * @return Collection<string, Product>
     */
    public static function ensure(): Collection
    {
        $out = collect();

        foreach (self::NAMES as $name) {
            $out[$name] = Product::query()->firstOrCreate(
                ['name' => $name],
                ['unit' => 'liter', 'status' => true]
            );

            if (! $out[$name]->status) {
                $out[$name]->update(['status' => true, 'unit' => 'liter']);
            }
        }

        // Soft-disable any other fuel products so they never appear in dropdowns/reports.
        Product::query()
            ->whereNotIn('name', self::NAMES)
            ->where('status', true)
            ->update(['status' => false]);

        return $out;
    }

    /**
     * Active Petrol + Diesel only, ordered Petrol then Diesel.
     *
     * @return Collection<int, Product>
     */
    public static function all(): Collection
    {
        self::ensure();

        return Product::query()
            ->whereIn('name', self::NAMES)
            ->where('status', true)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->name, self::NAMES, true))
            ->values();
    }

    public static function petrol(): Product
    {
        return self::ensure()->get(self::PETROL);
    }

    public static function diesel(): Product
    {
        return self::ensure()->get(self::DIESEL);
    }

    /**
     * @return array{petrol: int, diesel: int}
     */
    public static function ids(): array
    {
        $all = self::ensure();

        return [
            'petrol' => (int) $all->get(self::PETROL)->id,
            'diesel' => (int) $all->get(self::DIESEL)->id,
        ];
    }

    public static function keyFor(Product|string $product): string
    {
        $name = $product instanceof Product ? $product->name : $product;

        return strtolower($name);
    }
}
