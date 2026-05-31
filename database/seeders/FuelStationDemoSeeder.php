<?php

namespace Database\Seeders;

use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\Nozzle;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Tank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FuelStationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeStationLayout();

        $productIds = $this->seedProducts();
        $tankIds = $this->seedTanks($productIds);
        $dispenserIds = $this->seedDispensers();
        $this->seedNozzles($dispenserIds, $tankIds, $productIds);
        $this->seedEmployees();
    }

    private function wipeStationLayout(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'employee_shifts',
            'owner_fuel_usages',
            'tank_dip_readings',
            'tank_refills',
            'expenses',
            'nozzles',
            'dispensers',
            'tanks',
            'product_prices',
            'products',
            'employees',
            'shifts',
        ] as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function seedProducts(): array
    {
        $petrol = Product::create(['name' => 'Petrol', 'unit' => 'liter', 'status' => true]);
        $diesel = Product::create(['name' => 'Diesel', 'unit' => 'liter', 'status' => true]);

        ProductPrice::create([
            'product_id' => $petrol->id,
            'price' => 381.00,
            'effective_from' => now()->startOfDay(),
            'created_by' => 1,
        ]);

        ProductPrice::create([
            'product_id' => $diesel->id,
            'price' => 380.00,
            'effective_from' => now()->startOfDay(),
            'created_by' => 1,
        ]);

        return ['petrol' => $petrol->id, 'diesel' => $diesel->id];
    }

    private function seedTanks(array $productIds): array
    {
        $petrol = Tank::create([
            'product_id' => $productIds['petrol'],
            'tank_number' => 'T-PET',
            'capacity_liters' => 22000,
            'current_stock_liters' => 0,
            'minimum_level' => 2500,
            'status' => true,
        ]);

        $diesel = Tank::create([
            'product_id' => $productIds['diesel'],
            'tank_number' => 'T-DSL',
            'capacity_liters' => 45000,
            'current_stock_liters' => 0,
            'minimum_level' => 5000,
            'status' => true,
        ]);

        return ['petrol' => $petrol->id, 'diesel' => $diesel->id];
    }

    private function seedDispensers(): array
    {
        $petrolDisp = Dispenser::create([
            'dispenser_code' => 'D-P01',
            'company' => 'Wayne',
            'model' => 'Petrol Unit',
            'status' => true,
        ]);

        $diesel1 = Dispenser::create([
            'dispenser_code' => 'D-D01',
            'company' => 'Tatsuno',
            'model' => 'Diesel Unit 1',
            'status' => true,
        ]);

        $diesel2 = Dispenser::create([
            'dispenser_code' => 'D-D02',
            'company' => 'Tatsuno',
            'model' => 'Diesel Unit 2',
            'status' => true,
        ]);

        return ['petrol' => $petrolDisp->id, 'diesel1' => $diesel1->id, 'diesel2' => $diesel2->id];
    }

    private function seedNozzles(array $dispenserIds, array $tankIds, array $productIds): void
    {
        Nozzle::create([
            'dispenser_id' => $dispenserIds['petrol'],
            'tank_id' => $tankIds['petrol'],
            'product_id' => $productIds['petrol'],
            'nozzle_number' => 'N-P01',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        Nozzle::create([
            'dispenser_id' => $dispenserIds['petrol'],
            'tank_id' => $tankIds['petrol'],
            'product_id' => $productIds['petrol'],
            'nozzle_number' => 'N-P02',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel1'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D01',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel1'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D02',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel2'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D03',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel2'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D04',
            'current_meter_reading' => 0,
            'status' => true,
        ]);
    }

    private function seedEmployees(): void
    {
        Employee::create([
            'employee_code' => 'EMP-001',
            'name' => 'Ali Hassan',
            'cnic' => '35201-1234567-1',
            'phone' => '03001234567',
            'salary' => 35000,
            'joining_date' => now()->subMonths(8),
            'status' => true,
        ]);
    }
}
