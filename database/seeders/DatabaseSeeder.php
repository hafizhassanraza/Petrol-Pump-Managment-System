<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Tank;
use App\Models\TankDipReading;
use App\Models\Dispenser;
use App\Models\Nozzle;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\EmployeeShift;
use App\Models\TankRefill;
use App\Models\OwnerFuelUsage;
use App\Models\Expense;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        User::insert([
            [
                'name' => 'Owner',
                'email' => 'owner@test.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@test.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        Product::insert([
            [
                'name' => 'Petrol',
                'unit' => 'liter',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Diesel',
                'unit' => 'liter',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hi-Octane',
                'unit' => 'liter',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | PRODUCT PRICES
        |--------------------------------------------------------------------------
        */

        ProductPrice::insert([
            [
                'product_id' => 1,
                'price' => 275,
                'effective_from' => now(),
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 2,
                'price' => 285,
                'effective_from' => now(),
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 3,
                'price' => 300,
                'effective_from' => now(),
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | TANKS
        |--------------------------------------------------------------------------
        */

        Tank::insert([
            [
                'product_id' => 1,
                'tank_number' => 'T-01',
                'capacity_liters' => 20000,
                'current_stock_liters' => 15000,
                'minimum_level' => 2000,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 2,
                'tank_number' => 'T-02',
                'capacity_liters' => 15000,
                'current_stock_liters' => 10000,
                'minimum_level' => 1500,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 3,
                'tank_number' => 'T-03',
                'capacity_liters' => 10000,
                'current_stock_liters' => 7000,
                'minimum_level' => 1000,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | TANK DIP READINGS
        |--------------------------------------------------------------------------
        */

        TankDipReading::insert([
            [
                'tank_id' => 1,
                'reading_datetime' => now(),
                'measured_liters' => 14950,
                'notes' => 'Normal reading',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | DISPENSERS
        |--------------------------------------------------------------------------
        */

        Dispenser::insert([
            [
                'dispenser_code' => 'D-01',
                'company' => 'Wayne',
                'model' => 'X1',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dispenser_code' => 'D-02',
                'company' => 'Tatsuno',
                'model' => 'T2',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | NOZZLES
        |--------------------------------------------------------------------------
        */

        Nozzle::insert([
            [
                'dispenser_id' => 1,
                'tank_id' => 1,
                'product_id' => 1,
                'nozzle_number' => 'N-01',
                'current_meter_reading' => 1000,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dispenser_id' => 1,
                'tank_id' => 2,
                'product_id' => 2,
                'nozzle_number' => 'N-02',
                'current_meter_reading' => 2000,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dispenser_id' => 2,
                'tank_id' => 3,
                'product_id' => 3,
                'nozzle_number' => 'N-03',
                'current_meter_reading' => 3000,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */

        Employee::insert([
            [
                'employee_code' => 'EMP-001',
                'name' => 'Ali',
                'cnic' => '12345-1234567-1',
                'phone' => '03001234567',
                'salary' => 30000,
                'joining_date' => now(),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_code' => 'EMP-002',
                'name' => 'Ahmed',
                'cnic' => '12345-9876543-1',
                'phone' => '03111234567',
                'salary' => 32000,
                'joining_date' => now(),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | SHIFTS
        |--------------------------------------------------------------------------
        */

        Shift::insert([
            [
                'name' => 'Morning',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Evening',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE SHIFTS
        |--------------------------------------------------------------------------
        */

        EmployeeShift::insert([
            [
                'employee_id' => 1,
                'nozzle_id' => 1,
                'shift_id' => 1,
                'assigned_date' => now(),
                'opening_reading' => 1000,
                'closing_reading' => 1200,
                'testing_liters' => 5,
                'total_liters' => 195,
                'total_amount' => 53625,
                'cash_received' => 53625,
                'shortage_amount' => 0,
                'extra_amount' => 0,
                'submitted_at' => now(),
                'verified_by' => 1,
                'status' => 'verified',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | TANK REFILLS
        |--------------------------------------------------------------------------
        */

        TankRefill::insert([
            [
                'tank_id' => 1,
                'product_id' => 1,
                'invoice_no' => 'INV-001',
                'quantity_liters' => 5000,
                'purchase_rate' => 250,
                'total_amount' => 1250000,
                'received_datetime' => now(),
                'notes' => 'Refill received',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | OWNER FUEL USAGE
        |--------------------------------------------------------------------------
        */

        OwnerFuelUsage::insert([
            [
                'product_id' => 1,
                'nozzle_id' => 1,
                'employee_id' => 1,
                'vehicle_no' => 'LEA-1234',
                'person_name' => 'Owner',
                'purpose' => 'Personal Car',
                'liters' => 20,
                'price_per_liter' => 275,
                'total_amount' => 5500,
                'usage_datetime' => now(),
                'notes' => 'Personal usage',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);



        /*
        |--------------------------------------------------------------------------
        | EXPENSES
        |--------------------------------------------------------------------------
        */

        Expense::insert([
            [
                'expense_type' => 'Electricity',
                'amount' => 25000,
                'expense_date' => now(),
                'notes' => 'Monthly bill',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'expense_type' => 'Internet',
                'amount' => 5000,
                'expense_date' => now(),
                'notes' => 'Office internet',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}