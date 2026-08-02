<?php

namespace Database\Seeders;

use App\Models\Dispenser;
use App\Models\Nozzle;
use App\Models\ProductPrice;
use App\Models\Tank;
use App\Models\User;
use App\Support\FuelProducts;
use Illuminate\Database\Seeder;

/**
 * Production baseline: admin user, Petrol/Diesel @ price 0, tanks, dispensers, nozzles.
 *
 * Run alone on server:
 *   php artisan db:seed --class=ProdSeeder
 */
class ProdSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $adminId = (int) (User::query()->where('email', 'admin@example.com')->value('id') ?? 1);

        $products = FuelProducts::ensure();
        $petrol = $products->get(FuelProducts::PETROL);
        $diesel = $products->get(FuelProducts::DIESEL);

        foreach ([$petrol, $diesel] as $product) {
            if (! ProductPrice::query()->where('product_id', $product->id)->exists()) {
                ProductPrice::create([
                    'product_id' => $product->id,
                    'price' => 0,
                    'effective_from' => now()->startOfDay(),
                    'created_by' => $adminId,
                ]);
            }
        }

        $tankPetrol = Tank::updateOrCreate(
            ['tank_number' => 'T-PET'],
            [
                'product_id' => $petrol->id,
                'capacity_liters' => 22000,
                'current_stock_liters' => 0,
                'minimum_level' => 2500,
                'status' => true,
            ]
        );

        $tankDiesel = Tank::updateOrCreate(
            ['tank_number' => 'T-DSL'],
            [
                'product_id' => $diesel->id,
                'capacity_liters' => 45000,
                'current_stock_liters' => 0,
                'minimum_level' => 5000,
                'status' => true,
            ]
        );

        $dispenserPetrol = Dispenser::updateOrCreate(
            ['dispenser_code' => 'D-P01'],
            ['company' => 'Wayne', 'model' => 'Petrol Unit', 'status' => true]
        );

        $dispenserDiesel1 = Dispenser::updateOrCreate(
            ['dispenser_code' => 'D-D01'],
            ['company' => 'Tatsuno', 'model' => 'Diesel Unit 1', 'status' => true]
        );

        $dispenserDiesel2 = Dispenser::updateOrCreate(
            ['dispenser_code' => 'D-D02'],
            ['company' => 'Tatsuno', 'model' => 'Diesel Unit 2', 'status' => true]
        );

        foreach (['N-P01', 'N-P02'] as $code) {
            Nozzle::updateOrCreate(
                ['nozzle_number' => $code],
                [
                    'dispenser_id' => $dispenserPetrol->id,
                    'tank_id' => $tankPetrol->id,
                    'product_id' => $petrol->id,
                    'current_meter_reading' => 0,
                    'status' => true,
                ]
            );
        }

        $dieselNozzles = [
            'N-D01' => $dispenserDiesel1->id,
            'N-D02' => $dispenserDiesel1->id,
            'N-D03' => $dispenserDiesel2->id,
            'N-D04' => $dispenserDiesel2->id,
        ];

        foreach ($dieselNozzles as $code => $dispenserId) {
            Nozzle::updateOrCreate(
                ['nozzle_number' => $code],
                [
                    'dispenser_id' => $dispenserId,
                    'tank_id' => $tankDiesel->id,
                    'product_id' => $diesel->id,
                    'current_meter_reading' => 0,
                    'status' => true,
                ]
            );
        }

        $this->command?->info('Prod seeders done: admin, fuel price 0, tanks, dispensers, nozzles.');
    }
}
