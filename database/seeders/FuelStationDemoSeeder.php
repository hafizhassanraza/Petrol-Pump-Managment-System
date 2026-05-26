<?php

namespace Database\Seeders;

use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\TankDipReading;
use App\Models\TankRefill;
use App\Models\User;
use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class FuelStationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeOperationalData();

        $this->seedUsers();
        $productIds = $this->seedProductsAndPrices();
        $tankIds = $this->seedTanks($productIds);
        $dispenserIds = $this->seedDispensers();
        $nozzleIds = $this->seedNozzles($dispenserIds, $tankIds, $productIds);
        $employeeIds = $this->seedEmployees();
        $shiftId = $this->seedShift();
        $this->seedEmployeeShifts($nozzleIds, $employeeIds, $shiftId, $productIds);
        $this->seedRefills($tankIds, $productIds);
        $this->seedDipReadings($tankIds);
        $this->seedOwnerFuel($nozzleIds, $productIds, $employeeIds);
        $this->seedExpenses();
    }

    private function wipeOperationalData(): void
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

    private function seedUsers(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('mail@1234'),
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedProductsAndPrices(): array
    {
        $petrol = Product::create(['name' => 'Petrol', 'unit' => 'liter', 'status' => true]);
        $diesel = Product::create(['name' => 'Diesel', 'unit' => 'liter', 'status' => true]);

        ProductPrice::create([
            'product_id' => $petrol->id,
            'price' => 280.00,
            'effective_from' => now()->subDays(30),
            'created_by' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $petrol->id,
            'price' => 285.00,
            'effective_from' => now()->subDays(7),
            'created_by' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $diesel->id,
            'price' => 290.00,
            'effective_from' => now()->subDays(30),
            'created_by' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $diesel->id,
            'price' => 295.00,
            'effective_from' => now()->subDays(7),
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
            'current_stock_liters' => 16800,
            'minimum_level' => 2500,
            'status' => true,
        ]);

        $diesel = Tank::create([
            'product_id' => $productIds['diesel'],
            'tank_number' => 'T-DSL',
            'capacity_liters' => 45000,
            'current_stock_liters' => 33500,
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

    private function seedNozzles(array $dispenserIds, array $tankIds, array $productIds): array
    {
        $nozzles = [];

        // Petrol dispenser — 2 nozzles on petrol tank
        $nozzles['P1'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['petrol'],
            'tank_id' => $tankIds['petrol'],
            'product_id' => $productIds['petrol'],
            'nozzle_number' => 'N-P01',
            'current_meter_reading' => 125400,
            'status' => true,
        ]);
        $nozzles['P2'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['petrol'],
            'tank_id' => $tankIds['petrol'],
            'product_id' => $productIds['petrol'],
            'nozzle_number' => 'N-P02',
            'current_meter_reading' => 98450,
            'status' => true,
        ]);

        // Diesel dispenser 1 — 2 nozzles
        $nozzles['D1A'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel1'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D01',
            'current_meter_reading' => 452100,
            'status' => true,
        ]);
        $nozzles['D1B'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel1'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D02',
            'current_meter_reading' => 451880,
            'status' => true,
        ]);

        // Diesel dispenser 2 — 2 nozzles
        $nozzles['D2A'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel2'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D03',
            'current_meter_reading' => 318200,
            'status' => true,
        ]);
        $nozzles['D2B'] = Nozzle::create([
            'dispenser_id' => $dispenserIds['diesel2'],
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'nozzle_number' => 'N-D04',
            'current_meter_reading' => 317950,
            'status' => true,
        ]);

        return [
            'P1' => $nozzles['P1']->id,
            'P2' => $nozzles['P2']->id,
            'D1A' => $nozzles['D1A']->id,
            'D1B' => $nozzles['D1B']->id,
            'D2A' => $nozzles['D2A']->id,
            'D2B' => $nozzles['D2B']->id,
        ];
    }

    private function seedEmployees(): array
    {
        $e1 = Employee::create([
            'employee_code' => 'EMP-001',
            'name' => 'Ali Hassan',
            'cnic' => '35201-1234567-1',
            'phone' => '03001234567',
            'salary' => 35000,
            'joining_date' => now()->subMonths(8),
            'status' => true,
        ]);
        $e2 = Employee::create([
            'employee_code' => 'EMP-002',
            'name' => 'Ahmed Khan',
            'cnic' => '35201-9876543-1',
            'phone' => '03111234567',
            'salary' => 32000,
            'joining_date' => now()->subMonths(5),
            'status' => true,
        ]);
        $e3 = Employee::create([
            'employee_code' => 'EMP-003',
            'name' => 'Usman Raza',
            'cnic' => '35201-5555555-1',
            'phone' => '03221234567',
            'salary' => 30000,
            'joining_date' => now()->subMonths(2),
            'status' => true,
        ]);

        return [$e1->id, $e2->id, $e3->id];
    }

    private function seedShift(): int
    {
        $shift = Shift::create([
            'name' => 'Daily Shift (9 AM – 9 AM)',
            'start_time' => '09:00:00',
            'end_time' => '09:00:00',
        ]);

        return $shift->id;
    }

    private function seedEmployeeShifts(array $nozzleIds, array $employeeIds, int $shiftId, array $productIds): void
    {
        $petrolPrice = 285.00;
        $dieselPrice = 295.00;

        $samples = [
            ['key' => 'P1', 'price' => $petrolPrice, 'liters' => [155, 142, 168, 131, 149, 137, 160]],
            ['key' => 'P2', 'price' => $petrolPrice, 'liters' => [128, 135, 122, 140, 130, 145, 138]],
            ['key' => 'D1A', 'price' => $dieselPrice, 'liters' => [425, 398, 440, 412, 385, 430, 405]],
            ['key' => 'D1B', 'price' => $dieselPrice, 'liters' => [390, 375, 400, 368, 382, 395, 410]],
            ['key' => 'D2A', 'price' => $dieselPrice, 'liters' => [355, 340, 370, 348, 360, 375, 365]],
            ['key' => 'D2B', 'price' => $dieselPrice, 'liters' => [332, 318, 345, 325, 338, 350, 342]],
        ];

        foreach ($samples as $idx => $sample) {
            $nozzleId = $nozzleIds[$sample['key']];
            $nozzle = Nozzle::find($nozzleId);
            $opening = (float) $nozzle->current_meter_reading - 2500;

            for ($d = 6; $d >= 1; $d--) {
                $businessDate = BusinessDayService::currentBusinessDate()->copy()->subDays($d);
                $netLiters = $sample['liters'][6 - $d];
                $testing = 2;
                $closing = $opening + $netLiters + $testing;
                $amount = round($netLiters * $sample['price'], 2);

                EmployeeShift::create([
                    'employee_id' => $employeeIds[$idx % 3],
                    'nozzle_id' => $nozzleId,
                    'shift_id' => $shiftId,
                    'assigned_date' => $businessDate->toDateString(),
                    'opening_reading' => $opening,
                    'closing_reading' => $closing,
                    'testing_liters' => $testing,
                    'total_liters' => $netLiters,
                    'price_per_liter' => $sample['price'],
                    'total_amount' => $amount,
                    'cash_received' => $amount,
                    'online_received' => 0,
                    'shortage_amount' => 0,
                    'extra_amount' => 0,
                    'submitted_at' => $businessDate->copy()->setTime(20, 30),
                    'verified_by' => $d <= 2 ? 1 : null,
                    'status' => $d <= 2 ? 'verified' : 'submitted',
                    'created_at' => $businessDate->copy()->setTime(9, 0),
                    'updated_at' => $businessDate->copy()->setTime(20, 30),
                ]);

                $opening = $closing;
            }

            $nozzle->update(['current_meter_reading' => $opening]);
        }

        // Active shift for current business day on N-P02 (second petrol nozzle)
        $activeNozzleId = $nozzleIds['P2'];
        $activeMeter = (float) Nozzle::find($activeNozzleId)->current_meter_reading;

        EmployeeShift::create([
            'employee_id' => $employeeIds[1],
            'nozzle_id' => $activeNozzleId,
            'shift_id' => $shiftId,
            'assigned_date' => BusinessDayService::currentBusinessDate()->toDateString(),
            'opening_reading' => $activeMeter,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRefills(array $tankIds, array $productIds): void
    {
        TankRefill::create([
            'tank_id' => $tankIds['petrol'],
            'product_id' => $productIds['petrol'],
            'invoice_no' => 'INV-P-001',
            'quantity_liters' => 8000,
            'purchase_rate' => 255,
            'total_amount' => 2040000,
            'received_datetime' => now()->subDays(12),
            'notes' => 'Petrol delivery — 22000 L tank',
            'created_by' => 1,
        ]);

        TankRefill::create([
            'tank_id' => $tankIds['diesel'],
            'product_id' => $productIds['diesel'],
            'invoice_no' => 'INV-D-001',
            'quantity_liters' => 15000,
            'purchase_rate' => 265,
            'total_amount' => 3975000,
            'received_datetime' => now()->subDays(8),
            'notes' => 'Diesel delivery — 45000 L tank',
            'created_by' => 1,
        ]);
    }

    private function seedDipReadings(array $tankIds): void
    {
        $petrolTank = Tank::find($tankIds['petrol']);
        TankDipReading::create([
            'tank_id' => $tankIds['petrol'],
            'reading_datetime' => now()->subDays(2),
            'measured_liters' => 16750,
            'system_stock_liters' => $petrolTank->current_stock_liters,
            'difference_liters' => 16750 - $petrolTank->current_stock_liters,
            'stock_reconciled' => false,
            'notes' => 'Routine dip — petrol tank',
            'created_by' => 1,
        ]);

        $dieselTank = Tank::find($tankIds['diesel']);
        TankDipReading::create([
            'tank_id' => $tankIds['diesel'],
            'reading_datetime' => now()->subDays(2),
            'measured_liters' => 33420,
            'system_stock_liters' => $dieselTank->current_stock_liters,
            'difference_liters' => 33420 - $dieselTank->current_stock_liters,
            'stock_reconciled' => false,
            'notes' => 'Routine dip — diesel tank',
            'created_by' => 1,
        ]);
    }

    private function seedOwnerFuel(array $nozzleIds, array $productIds, array $employeeIds): void
    {
        OwnerFuelUsage::create([
            'product_id' => $productIds['petrol'],
            'nozzle_id' => $nozzleIds['P1'],
            'employee_id' => $employeeIds[0],
            'vehicle_no' => 'LEA-1234',
            'person_name' => 'Owner',
            'purpose' => 'Personal vehicle',
            'liters' => 30,
            'price_per_liter' => 285,
            'total_amount' => 8550,
            'usage_datetime' => now()->subDays(3),
            'created_by' => 1,
        ]);

        OwnerFuelUsage::create([
            'product_id' => $productIds['diesel'],
            'nozzle_id' => $nozzleIds['D1A'],
            'employee_id' => $employeeIds[1],
            'vehicle_no' => 'ABC-5678',
            'person_name' => 'Manager',
            'purpose' => 'Generator / vehicle',
            'liters' => 50,
            'price_per_liter' => 295,
            'total_amount' => 14750,
            'usage_datetime' => now()->subDay(),
            'created_by' => 1,
        ]);
    }

    private function seedExpenses(): void
    {
        foreach ([
            ['Electricity', 32000, -2],
            ['Internet', 6000, -5],
            ['Staff Advance', 12000, -1],
            ['Dispenser Maintenance', 9500, -4],
            ['Generator Diesel', 8000, 0],
        ] as [$type, $amount, $days]) {
            Expense::create([
                'expense_type' => $type,
                'amount' => $amount,
                'expense_date' => now()->addDays($days)->toDateString(),
                'notes' => 'Afreen Petroleum',
                'created_by' => 1,
            ]);
        }
    }
}
