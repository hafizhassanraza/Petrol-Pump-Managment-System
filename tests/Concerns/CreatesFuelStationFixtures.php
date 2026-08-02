<?php

namespace Tests\Concerns;

use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\MobilOilProduct;
use App\Models\Nozzle;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\User;
use App\Services\BusinessDayService;
use App\Services\MobilOilPriceService;
use App\Services\ProductPriceService;
trait CreatesFuelStationFixtures
{
    protected function createOwner(): User
    {
        return User::factory()->create([
            'role' => 'owner',
        ]);
    }

    /**
     * @return array{
     *     user: User,
     *     product: Product,
     *     tank: Tank,
     *     dispenser: Dispenser,
     *     nozzle: Nozzle,
     *     employee: Employee,
     *     shift: Shift
     * }
     */
    protected function createFuelStationGraph(
        float $tankStock = 5000,
        float $pricePerLiter = 300,
        float $meterReading = 1000,
    ): array {
        $user = $this->createOwner();

        $fuels = \App\Support\FuelProducts::ensure();
        $product = $fuels->get(\App\Support\FuelProducts::PETROL);
        // Keep Diesel present for fixed two-product reports/dropdowns.
        $fuels->get(\App\Support\FuelProducts::DIESEL);

        ProductPriceService::setPrice($product->id, $pricePerLiter, now(), $user->id);

        $tank = Tank::create([
            'product_id' => $product->id,
            'tank_number' => 'T-TEST-01',
            'capacity_liters' => 10000,
            'current_stock_liters' => $tankStock,
            'minimum_level' => 500,
            'status' => true,
        ]);

        $dispenser = Dispenser::create([
            'dispenser_code' => 'D-TEST-01',
            'company' => 'TestCo',
            'model' => 'Unit',
            'status' => true,
        ]);

        $nozzle = Nozzle::create([
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'product_id' => $product->id,
            'nozzle_number' => 'N-TEST-01',
            'current_meter_reading' => $meterReading,
            'status' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-TEST-01',
            'name' => 'Test Attendant',
            'status' => true,
            'salary' => 30000,
            'joining_date' => now()->subMonth(),
        ]);

        $shift = BusinessDayService::defaultShift();

        return compact('user', 'product', 'tank', 'dispenser', 'nozzle', 'employee', 'shift');
    }

    protected function travelToBusinessHours(): void
    {
        $this->travelTo(now()->setTime(10, 0, 0));
    }

    /**
     * @return array{user: User, product: MobilOilProduct}
     */
    protected function createMobilOilGraph(
        float $stock = 50,
        float $price = 850,
        string $name = 'Mobil Super 1L',
    ): array {
        $user = $this->createOwner();

        $product = MobilOilProduct::create([
            'name' => $name,
            'sku' => 'MOB-TEST-01',
            'unit' => 'bottle',
            'current_stock_qty' => $stock,
            'minimum_level' => 5,
            'status' => true,
        ]);

        MobilOilPriceService::setPrice($product->id, $price, now(), $user->id);

        return compact('user', 'product');
    }
}
