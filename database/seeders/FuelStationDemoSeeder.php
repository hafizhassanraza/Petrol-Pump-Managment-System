<?php

namespace Database\Seeders;

use App\Models\CashTransaction;
use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\MobilOilProduct;
use App\Models\MobilOilPurchase;
use App\Models\MobilOilSale;
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
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FuelStationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeStationLayout();

        $adminId = $this->adminUserId();
        $productIds = $this->seedProducts($adminId);
        $tankIds = $this->seedTanks($productIds);
        $dispenserIds = $this->seedDispensers();
        $nozzleIds = $this->seedNozzles($dispenserIds, $tankIds, $productIds);
        $employeeIds = $this->seedEmployees();
        $shiftId = $this->seedShifts();
        $this->seedMobilOil($adminId);

        $this->seedOperationalHistory(
            $adminId,
            $productIds,
            $tankIds,
            $nozzleIds,
            $employeeIds,
            $shiftId
        );

        $this->command?->info('Demo data seeded: layout + ~14 days of sales, purchases, expenses, and attendance.');
    }

    private function seedMobilOil(int $adminId): void
    {
        $this->call(MobilOilSeeder::class, false, ['createdBy' => $adminId]);
    }

    private function wipeStationLayout(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'employee_attendances',
            'employee_shifts',
            'owner_fuel_usages',
            'tank_dip_readings',
            'tank_refills',
            'expenses',
            'cash_transactions',
            'mobil_oil_sales',
            'mobil_oil_purchases',
            'mobil_oil_prices',
            'mobil_oil_products',
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

    private function seedProducts(int $createdBy): array
    {
        $products = \App\Support\FuelProducts::ensure();
        $petrol = $products->get(\App\Support\FuelProducts::PETROL);
        $diesel = $products->get(\App\Support\FuelProducts::DIESEL);

        // Price history so reports show rate changes
        $priceHistory = [
            [$petrol->id, 360.00, now()->subDays(20)->setTime(9, 0)],
            [$petrol->id, 372.00, now()->subDays(10)->setTime(9, 0)],
            [$petrol->id, 381.00, now()->subDays(3)->setTime(9, 0)],
            [$diesel->id, 355.00, now()->subDays(20)->setTime(9, 0)],
            [$diesel->id, 368.00, now()->subDays(10)->setTime(9, 0)],
            [$diesel->id, 380.00, now()->subDays(3)->setTime(9, 0)],
        ];

        foreach ($priceHistory as [$productId, $price, $from]) {
            ProductPrice::create([
                'product_id' => $productId,
                'price' => $price,
                'effective_from' => $from,
                'created_by' => $createdBy,
            ]);
        }

        return ['petrol' => $petrol->id, 'diesel' => $diesel->id];
    }

    private function adminUserId(): int
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        if (! $admin) {
            throw new RuntimeException(
                'Admin user not found. Run AdminUserSeeder before FuelStationDemoSeeder.'
            );
        }

        return $admin->id;
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
        return [
            'petrol' => Dispenser::create([
                'dispenser_code' => 'D-P01',
                'company' => 'Wayne',
                'model' => 'Petrol Unit',
                'status' => true,
            ])->id,
            'diesel1' => Dispenser::create([
                'dispenser_code' => 'D-D01',
                'company' => 'Tatsuno',
                'model' => 'Diesel Unit 1',
                'status' => true,
            ])->id,
            'diesel2' => Dispenser::create([
                'dispenser_code' => 'D-D02',
                'company' => 'Tatsuno',
                'model' => 'Diesel Unit 2',
                'status' => true,
            ])->id,
        ];
    }

    /**
     * @return array{petrol: list<int>, diesel: list<int>}
     */
    private function seedNozzles(array $dispenserIds, array $tankIds, array $productIds): array
    {
        $petrol = [];
        $diesel = [];

        foreach (['N-P01', 'N-P02'] as $code) {
            $petrol[] = Nozzle::create([
                'dispenser_id' => $dispenserIds['petrol'],
                'tank_id' => $tankIds['petrol'],
                'product_id' => $productIds['petrol'],
                'nozzle_number' => $code,
                'current_meter_reading' => 10000,
                'status' => true,
            ])->id;
        }

        $dieselMap = [
            'N-D01' => $dispenserIds['diesel1'],
            'N-D02' => $dispenserIds['diesel1'],
            'N-D03' => $dispenserIds['diesel2'],
            'N-D04' => $dispenserIds['diesel2'],
        ];

        foreach ($dieselMap as $code => $dispenserId) {
            $diesel[] = Nozzle::create([
                'dispenser_id' => $dispenserId,
                'tank_id' => $tankIds['diesel'],
                'product_id' => $productIds['diesel'],
                'nozzle_number' => $code,
                'current_meter_reading' => 20000,
                'status' => true,
            ])->id;
        }

        return ['petrol' => $petrol, 'diesel' => $diesel];
    }

    /**
     * @return list<int>
     */
    private function seedEmployees(): array
    {
        $rows = [
            ['EMP-001', 'Ali Hassan', '35201-1234567-1', '03001234567', 35000, 8],
            ['EMP-002', 'Usman Khan', '35202-2345678-2', '03011234567', 32000, 5],
            ['EMP-003', 'Bilal Ahmed', '35203-3456789-3', '03021234567', 30000, 3],
            ['EMP-004', 'Sara Malik', '35204-4567890-4', '03031234567', 28000, 2],
        ];

        $ids = [];
        foreach ($rows as [$code, $name, $cnic, $phone, $salary, $months]) {
            $ids[] = Employee::create([
                'employee_code' => $code,
                'name' => $name,
                'cnic' => $cnic,
                'phone' => $phone,
                'salary' => $salary,
                'joining_date' => now()->subMonths($months),
                'status' => true,
            ])->id;
        }

        return $ids;
    }

    private function seedShifts(): int
    {
        return Shift::firstOrCreate(
            ['name' => 'Business Day (9 AM – 9 AM)'],
            [
                'start_time' => '09:00:00',
                'end_time' => '09:00:00',
            ]
        )->id;
    }

    /**
     * @param  array{petrol: int, diesel: int}  $productIds
     * @param  array{petrol: int, diesel: int}  $tankIds
     * @param  array{petrol: list<int>, diesel: list<int>}  $nozzleIds
     * @param  list<int>  $employeeIds
     */
    private function seedOperationalHistory(
        int $adminId,
        array $productIds,
        array $tankIds,
        array $nozzleIds,
        array $employeeIds,
        int $shiftId
    ): void {
        $petrolTank = Tank::findOrFail($tankIds['petrol']);
        $dieselTank = Tank::findOrFail($tankIds['diesel']);
        $petrolNozzles = Nozzle::whereIn('id', $nozzleIds['petrol'])->get()->values();
        $dieselNozzles = Nozzle::whereIn('id', $nozzleIds['diesel'])->get()->values();

        // Opening stock via large refills ~14 days ago
        $this->applyRefill($petrolTank, $productIds['petrol'], 12000, 345.00, now()->subDays(14)->setTime(10, 0), 'INV-P-001', $adminId);
        $this->applyRefill($dieselTank, $productIds['diesel'], 20000, 340.00, now()->subDays(14)->setTime(11, 0), 'INV-D-001', $adminId);

        // Mid-period refill with higher purchase rate
        $this->applyRefill($petrolTank, $productIds['petrol'], 8000, 355.00, now()->subDays(7)->setTime(10, 30), 'INV-P-002', $adminId);
        $this->applyRefill($dieselTank, $productIds['diesel'], 12000, 350.00, now()->subDays(7)->setTime(11, 30), 'INV-D-002', $adminId);

        // Recent refill
        $this->applyRefill($petrolTank, $productIds['petrol'], 5000, 360.00, now()->subDays(2)->setTime(9, 45), 'INV-P-003', $adminId);
        $this->applyRefill($dieselTank, $productIds['diesel'], 8000, 358.00, now()->subDays(2)->setTime(10, 15), 'INV-D-003', $adminId);

        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $day = BusinessDayService::currentBusinessDate()->copy()->subDays($daysAgo);
            $dateStr = $day->toDateString();
            $saleAt = $day->copy()->setTime(18, 0);

            $petrolPrice = $this->priceAt($productIds['petrol'], $saleAt);
            $dieselPrice = $this->priceAt($productIds['diesel'], $saleAt);

            // Rotate employees across nozzles
            $emp1 = $employeeIds[$daysAgo % count($employeeIds)];
            $emp2 = $employeeIds[($daysAgo + 1) % count($employeeIds)];

            $petrolLiters = 450 + ($daysAgo % 5) * 40; // ~450–610 L
            $dieselLiters = 700 + ($daysAgo % 4) * 55; // ~700–865 L

            $this->closeShift(
                employeeId: $emp1,
                nozzle: $petrolNozzles[$daysAgo % $petrolNozzles->count()],
                tank: $petrolTank,
                shiftId: $shiftId,
                assignedDate: $dateStr,
                liters: $petrolLiters,
                price: $petrolPrice,
                testing: $daysAgo % 3 === 0 ? 5 : 0,
                cashRatio: 0.72,
                adminId: $adminId,
                verified: $daysAgo > 0
            );

            $this->closeShift(
                employeeId: $emp2,
                nozzle: $dieselNozzles[$daysAgo % $dieselNozzles->count()],
                tank: $dieselTank,
                shiftId: $shiftId,
                assignedDate: $dateStr,
                liters: $dieselLiters,
                price: $dieselPrice,
                testing: $daysAgo % 4 === 0 ? 8 : 0,
                cashRatio: 0.65,
                adminId: $adminId,
                verified: $daysAgo > 0
            );

            // Attendance for all staff
            foreach ($employeeIds as $index => $employeeId) {
                $status = match (true) {
                    $daysAgo === 5 && $index === 3 => 'on_leave',
                    $daysAgo % 9 === 0 && $index === 2 => 'late',
                    default => 'present',
                };

                EmployeeAttendance::create([
                    'employee_id' => $employeeId,
                    'attendance_date' => $dateStr,
                    'check_in' => $status === 'on_leave' ? null : ($status === 'late' ? '10:15:00' : '08:55:00'),
                    'check_out' => $status === 'on_leave' ? null : '21:05:00',
                    'status' => $status,
                    'notes' => $status === 'on_leave' ? 'Personal leave' : null,
                    'recorded_by' => $adminId,
                ]);
            }

            // Occasional expenses
            if ($daysAgo % 3 === 0) {
                Expense::create([
                    'expense_type' => ['Electricity Bill', 'Maintenance', 'Miscellaneous'][$daysAgo % 3],
                    'amount' => [8500, 3200, 1500][$daysAgo % 3],
                    'expense_date' => $dateStr,
                    'notes' => 'Demo seeded expense',
                    'created_by' => $adminId,
                ]);
            }

            // Cash in / out samples
            if ($daysAgo % 4 === 0) {
                CashTransaction::create([
                    'type' => CashTransaction::TYPE_IN,
                    'category' => 'Owner Investment',
                    'amount' => 50000,
                    'transaction_date' => $dateStr,
                    'payment_method' => 'cash',
                    'reference_no' => 'CI-' . str_pad((string) (14 - $daysAgo), 3, '0', STR_PAD_LEFT),
                    'notes' => 'Demo cash in',
                    'created_by' => $adminId,
                ]);
            }

            if ($daysAgo % 5 === 1) {
                CashTransaction::create([
                    'type' => CashTransaction::TYPE_OUT,
                    'category' => 'Bank Deposit',
                    'amount' => 25000 + ($daysAgo * 500),
                    'transaction_date' => $dateStr,
                    'payment_method' => 'cash',
                    'reference_no' => 'CO-' . str_pad((string) (14 - $daysAgo), 3, '0', STR_PAD_LEFT),
                    'notes' => 'Demo cash out to bank',
                    'created_by' => $adminId,
                ]);
            }

            // Salary expense once in the period
            if ($daysAgo === 1) {
                $salaryTotal = (float) Employee::where('status', 1)->sum('salary');
                Expense::create([
                    'expense_type' => 'Salary',
                    'amount' => $salaryTotal,
                    'expense_date' => $dateStr,
                    'notes' => 'Monthly payroll (demo)',
                    'created_by' => $adminId,
                ]);
            }
        }

        // Owner fuel usages
        foreach ([11, 6, 1] as $ago) {
            $at = now()->subDays($ago)->setTime(14, 30);
            $nozzle = $petrolNozzles->first();
            $price = $this->priceAt($productIds['petrol'], $at);
            $liters = 25 + $ago;

            OwnerFuelUsage::create([
                'product_id' => $productIds['petrol'],
                'nozzle_id' => $nozzle->id,
                'employee_id' => $employeeIds[0],
                'vehicle_no' => 'LEA-1234',
                'person_name' => 'Owner',
                'purpose' => 'Personal / generator',
                'liters' => $liters,
                'price_per_liter' => $price,
                'total_amount' => round($liters * $price, 2),
                'usage_datetime' => $at,
                'notes' => 'Demo owner usage',
                'created_by' => $adminId,
            ]);

            $petrolTank->decrement('current_stock_liters', $liters);
            $nozzle->increment('current_meter_reading', $liters);
        }

        // Tank dip readings (latest reconciled for petrol)
        TankDipReading::create([
            'tank_id' => $petrolTank->id,
            'reading_datetime' => now()->subDay()->setTime(8, 30),
            'measured_liters' => round((float) $petrolTank->fresh()->current_stock_liters - 15, 2),
            'system_stock_liters' => (float) $petrolTank->fresh()->current_stock_liters,
            'difference_liters' => -15,
            'stock_reconciled' => false,
            'notes' => 'Demo dip — small shortage',
            'created_by' => $adminId,
        ]);

        TankDipReading::create([
            'tank_id' => $dieselTank->id,
            'reading_datetime' => now()->subDay()->setTime(8, 40),
            'measured_liters' => (float) $dieselTank->fresh()->current_stock_liters,
            'system_stock_liters' => (float) $dieselTank->fresh()->current_stock_liters,
            'difference_liters' => 0,
            'stock_reconciled' => false,
            'notes' => 'Demo dip — matched',
            'created_by' => $adminId,
        ]);

        $this->seedMobilOilActivity($adminId, $employeeIds);
    }

    private function applyRefill(
        Tank $tank,
        int $productId,
        float $qty,
        float $rate,
        Carbon $receivedAt,
        string $invoice,
        int $adminId
    ): void {
        $before = (float) $tank->current_stock_liters;

        TankRefill::create([
            'tank_id' => $tank->id,
            'product_id' => $productId,
            'invoice_no' => $invoice,
            'quantity_liters' => $qty,
            'stock_before_liters' => $before,
            'purchase_rate' => $rate,
            'total_amount' => round($qty * $rate, 2),
            'received_datetime' => $receivedAt,
            'notes' => 'Demo tanker delivery',
            'created_by' => $adminId,
        ]);

        $tank->increment('current_stock_liters', $qty);
        $tank->refresh();
    }

    private function closeShift(
        int $employeeId,
        Nozzle $nozzle,
        Tank $tank,
        int $shiftId,
        string $assignedDate,
        float $liters,
        float $price,
        float $testing,
        float $cashRatio,
        int $adminId,
        bool $verified
    ): void {
        $nozzle->refresh();
        $opening = (float) $nozzle->current_meter_reading;
        $gross = $liters + $testing;
        $closing = $opening + $gross;
        $net = $liters;
        $amount = round($net * $price, 2);
        $cash = round($amount * $cashRatio, 2);
        $online = round($amount - $cash, 2);
        $diff = ($cash + $online) - $amount;

        EmployeeShift::create([
            'employee_id' => $employeeId,
            'nozzle_id' => $nozzle->id,
            'shift_id' => $shiftId,
            'assigned_date' => $assignedDate,
            'opening_reading' => $opening,
            'closing_reading' => $closing,
            'testing_liters' => $testing,
            'total_liters' => $net,
            'price_per_liter' => $price,
            'total_amount' => $amount,
            'cash_received' => $cash,
            'online_received' => $online,
            'shortage_amount' => $diff < 0 ? abs($diff) : 0,
            'extra_amount' => $diff > 0 ? $diff : 0,
            'submitted_at' => Carbon::parse($assignedDate)->setTime(21, 10),
            'status' => $verified ? 'verified' : 'submitted',
            'verified_by' => $verified ? $adminId : null,
        ]);

        $nozzle->update(['current_meter_reading' => $closing]);
        $tank->decrement('current_stock_liters', $net);
    }

    private function priceAt(int $productId, Carbon $at): float
    {
        $row = ProductPrice::where('product_id', $productId)
            ->where('effective_from', '<=', $at)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        return $row ? (float) $row->price : 0;
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function seedMobilOilActivity(int $adminId, array $employeeIds): void
    {
        $products = MobilOilProduct::orderBy('id')->get();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products as $index => $product) {
            $qty = 24 + ($index * 6);
            $rate = max(100, (float) ($product->prices()->latest('effective_from')->value('price') ?? 500) * 0.75);

            MobilOilPurchase::create([
                'mobil_oil_product_id' => $product->id,
                'invoice_no' => 'MO-INV-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'quantity' => $qty,
                'purchase_rate' => round($rate, 2),
                'total_amount' => round($qty * $rate, 2),
                'received_datetime' => now()->subDays(12 - $index)->setTime(12, 0),
                'notes' => 'Demo mobil oil stock',
                'created_by' => $adminId,
            ]);

            $product->increment('current_stock_qty', $qty);
        }

        $products = MobilOilProduct::orderBy('id')->get();
        foreach ($products->take(4) as $index => $product) {
            $qty = 2 + ($index % 3);
            $unitPrice = (float) ($product->prices()->latest('effective_from')->value('price') ?? 800);
            $available = (float) $product->fresh()->current_stock_qty;
            if ($available < $qty) {
                continue;
            }

            MobilOilSale::create([
                'mobil_oil_product_id' => $product->id,
                'employee_id' => $employeeIds[$index % count($employeeIds)],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_amount' => round($qty * $unitPrice, 2),
                'payment_method' => $index % 2 === 0 ? 'cash' : 'online',
                'sold_datetime' => now()->subDays(max(1, 8 - $index))->setTime(16, 20),
                'notes' => 'Demo retail sale',
                'created_by' => $adminId,
            ]);

            $product->decrement('current_stock_qty', $qty);
        }
    }
}
