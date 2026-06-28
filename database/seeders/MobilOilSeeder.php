<?php

namespace Database\Seeders;

use App\Models\MobilOilPrice;
use App\Models\MobilOilProduct;
use Illuminate\Database\Seeder;

class MobilOilSeeder extends Seeder
{
    public function run(int $createdBy = 1): void
    {
        $items = [
            [
                'name' => 'Mobil Super 1L',
                'sku' => 'MOB-SUP-1L',
                'unit' => 'bottle',
                'minimum_level' => 12,
                'price' => 850.00,
            ],
            [
                'name' => 'Mobil Super 4L',
                'sku' => 'MOB-SUP-4L',
                'unit' => 'bottle',
                'minimum_level' => 6,
                'price' => 3200.00,
            ],
            [
                'name' => 'Castrol GTX 1L',
                'sku' => 'CAS-GTX-1L',
                'unit' => 'bottle',
                'minimum_level' => 10,
                'price' => 780.00,
            ],
            [
                'name' => 'Shell Helix HX7 1L',
                'sku' => 'SHL-HX7-1L',
                'unit' => 'bottle',
                'minimum_level' => 10,
                'price' => 920.00,
            ],
            [
                'name' => 'Total Quartz 1L',
                'sku' => 'TOT-QTZ-1L',
                'unit' => 'bottle',
                'minimum_level' => 8,
                'price' => 750.00,
            ],
            [
                'name' => 'Mobil 1 Carton (12x1L)',
                'sku' => 'MOB-CTN-12',
                'unit' => 'carton',
                'minimum_level' => 2,
                'price' => 9600.00,
            ],
        ];

        $effectiveFrom = now()->startOfDay();

        foreach ($items as $item) {
            $product = MobilOilProduct::create([
                'name' => $item['name'],
                'sku' => $item['sku'],
                'unit' => $item['unit'],
                'current_stock_qty' => 0,
                'minimum_level' => $item['minimum_level'],
                'status' => true,
            ]);

            MobilOilPrice::create([
                'mobil_oil_product_id' => $product->id,
                'price' => $item['price'],
                'effective_from' => $effectiveFrom,
                'created_by' => $createdBy,
            ]);
        }
    }
}
