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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FuelStationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::where('email', 'owner@test.com')->exists()) {
            User::insert([
                [
                    'name' => 'Owner',
                    'email' => 'owner@test.com',
                    'password' => Hash::make('password'),
                    'role' => 'owner',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Manager',
                    'email' => 'manager@test.com',
                    'password' => Hash::make('password'),
                    'role' => 'manager',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        Product::insert([
            ['name' => 'Petrol', 'unit' => 'liter', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Diesel', 'unit' => 'liter', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hi-Octane', 'unit' => 'liter', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $priceHistory = [
            [1, 265, -60],
            [1, 270, -45],
            [1, 275, -30],
            [1, 280, -7],
            [2, 275, -60],
            [2, 280, -45],
            [2, 285, -30],
            [2, 290, -7],
            [3, 290, -60],
            [3, 295, -45],
            [3, 300, -30],
            [3, 305, -7],
        ];

        foreach ($priceHistory as [$productId, $price, $daysAgo]) {
            ProductPrice::create([
                'product_id' => $productId,
                'price' => $price,
                'effective_from' => now()->addDays($daysAgo),
                'created_by' => 1,
            ]);
        }

        Tank::insert([
            ['product_id' => 1, 'tank_number' => 'T-01', 'capacity_liters' => 20000, 'current_stock_liters' => 14500, 'minimum_level' => 2000, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'tank_number' => 'T-02', 'capacity_liters' => 15000, 'current_stock_liters' => 9800, 'minimum_level' => 1500, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 3, 'tank_number' => 'T-03', 'capacity_liters' => 10000, 'current_stock_liters' => 6500, 'minimum_level' => 1000, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Dispenser::insert([
            ['dispenser_code' => 'D-01', 'company' => 'Wayne', 'model' => 'X1', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dispenser_code' => 'D-02', 'company' => 'Tatsuno', 'model' => 'T2', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Nozzle::insert([
            ['dispenser_id' => 1, 'tank_id' => 1, 'product_id' => 1, 'nozzle_number' => 'N-01', 'current_meter_reading' => 15200, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dispenser_id' => 1, 'tank_id' => 2, 'product_id' => 2, 'nozzle_number' => 'N-02', 'current_meter_reading' => 8400, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['dispenser_id' => 2, 'tank_id' => 3, 'product_id' => 3, 'nozzle_number' => 'N-03', 'current_meter_reading' => 5100, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Employee::insert([
            ['employee_code' => 'EMP-001', 'name' => 'Ali Hassan', 'cnic' => '35201-1234567-1', 'phone' => '03001234567', 'salary' => 35000, 'joining_date' => now()->subMonths(6), 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['employee_code' => 'EMP-002', 'name' => 'Ahmed Khan', 'cnic' => '35201-9876543-1', 'phone' => '03111234567', 'salary' => 32000, 'joining_date' => now()->subMonths(4), 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['employee_code' => 'EMP-003', 'name' => 'Usman Raza', 'cnic' => '35201-5555555-1', 'phone' => '03221234567', 'salary' => 30000, 'joining_date' => now()->subMonths(2), 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Shift::insert([
            ['name' => 'Morning', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Evening', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Night', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        for ($d = 6; $d >= 0; $d--) {
            $date = Carbon::today()->subDays($d);
            $opening = 15000 + ($d * 120);
            $closing = $opening + 180 + ($d * 5);
            $net = $closing - $opening - 3;
            $price = 275 + ($d % 2);

            EmployeeShift::create([
                'employee_id' => ($d % 2) + 1,
                'nozzle_id' => 1,
                'shift_id' => ($d % 3) + 1,
                'assigned_date' => $date,
                'opening_reading' => $opening,
                'closing_reading' => $closing,
                'testing_liters' => 3,
                'total_liters' => $net,
                'price_per_liter' => $price,
                'total_amount' => round($net * $price, 2),
                'cash_received' => round($net * $price, 2),
                'online_received' => 0,
                'shortage_amount' => 0,
                'extra_amount' => 0,
                'submitted_at' => $date->copy()->setTime(14, 0),
                'verified_by' => $d < 2 ? 1 : null,
                'status' => $d < 2 ? 'verified' : 'submitted',
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        EmployeeShift::create([
            'employee_id' => 2,
            'nozzle_id' => 2,
            'shift_id' => 2,
            'assigned_date' => today(),
            'opening_reading' => 8400,
            'status' => 'active',
        ]);

        TankRefill::insert([
            ['tank_id' => 1, 'product_id' => 1, 'invoice_no' => 'INV-1001', 'quantity_liters' => 5000, 'purchase_rate' => 250, 'total_amount' => 1250000, 'received_datetime' => now()->subDays(10), 'notes' => 'Bulk petrol delivery', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['tank_id' => 2, 'product_id' => 2, 'invoice_no' => 'INV-1002', 'quantity_liters' => 3000, 'purchase_rate' => 260, 'total_amount' => 780000, 'received_datetime' => now()->subDays(5), 'notes' => 'Diesel refill', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        TankDipReading::create([
            'tank_id' => 1,
            'reading_datetime' => now()->subDays(2),
            'measured_liters' => 14480,
            'system_stock_liters' => 14500,
            'difference_liters' => -20,
            'stock_reconciled' => false,
            'notes' => 'Routine dip',
            'created_by' => 1,
        ]);

        OwnerFuelUsage::insert([
            ['product_id' => 1, 'nozzle_id' => 1, 'employee_id' => 1, 'vehicle_no' => 'LEA-1234', 'person_name' => 'Owner', 'purpose' => 'Personal', 'liters' => 25, 'price_per_liter' => 280, 'total_amount' => 7000, 'usage_datetime' => now()->subDays(3), 'notes' => null, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'nozzle_id' => 2, 'employee_id' => 2, 'vehicle_no' => 'ABC-999', 'person_name' => 'Manager', 'purpose' => 'Office vehicle', 'liters' => 15, 'price_per_liter' => 290, 'total_amount' => 4350, 'usage_datetime' => now()->subDay(), 'notes' => null, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $expenseTypes = [
            ['Electricity', 28000, -2],
            ['Internet', 5000, -5],
            ['Staff Salary Advance', 15000, -1],
            ['Maintenance', 8500, -3],
            ['Generator Fuel', 12000, 0],
        ];

        foreach ($expenseTypes as [$type, $amount, $days]) {
            Expense::create([
                'expense_type' => $type,
                'amount' => $amount,
                'expense_date' => now()->addDays($days)->toDateString(),
                'notes' => 'Demo expense',
                'created_by' => 1,
            ]);
        }
    }
}
