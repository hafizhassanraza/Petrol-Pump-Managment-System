<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Tank;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTank(float $stock = 1000, float $capacity = 5000): Tank
    {
        $product = Product::create(['name' => 'Stock Test', 'unit' => 'liter', 'status' => true]);

        return Tank::create([
            'product_id' => $product->id,
            'tank_number' => 'T-STOCK',
            'capacity_liters' => $capacity,
            'current_stock_liters' => $stock,
            'minimum_level' => 100,
            'status' => true,
        ]);
    }

    public function test_can_decrement_when_stock_sufficient(): void
    {
        $tank = $this->makeTank(1000);

        $this->assertTrue(StockService::canDecrement($tank, 500));
    }

    public function test_cannot_decrement_when_stock_insufficient(): void
    {
        $tank = $this->makeTank(100);

        $this->assertFalse(StockService::canDecrement($tank, 500));
    }

    public function test_decrement_reduces_stock(): void
    {
        $tank = $this->makeTank(1000);

        StockService::decrement($tank, 250);

        $this->assertEquals(750, (float) $tank->fresh()->current_stock_liters);
    }

    public function test_decrement_throws_when_insufficient(): void
    {
        $tank = $this->makeTank(50);

        $this->expectException(RuntimeException::class);

        StockService::decrement($tank, 100);
    }

    public function test_decrement_throws_for_zero_liters(): void
    {
        $tank = $this->makeTank(1000);

        $this->expectException(RuntimeException::class);

        StockService::decrement($tank, 0);
    }

    public function test_can_increment_within_capacity(): void
    {
        $tank = $this->makeTank(1000, 5000);

        $this->assertTrue(StockService::canIncrement($tank, 3000));
        $this->assertFalse(StockService::canIncrement($tank, 5000));
    }

    public function test_increment_increases_stock(): void
    {
        $tank = $this->makeTank(1000, 5000);

        StockService::increment($tank, 500);

        $this->assertEquals(1500, (float) $tank->fresh()->current_stock_liters);
    }

    public function test_reconcile_sets_physical_stock(): void
    {
        $tank = $this->makeTank(1000);

        StockService::reconcile($tank, 4321.5);

        $this->assertEquals(4321.5, (float) $tank->fresh()->current_stock_liters);
    }
}
