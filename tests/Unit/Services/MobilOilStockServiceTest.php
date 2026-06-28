<?php

namespace Tests\Unit\Services;

use App\Models\MobilOilProduct;
use App\Services\MobilOilStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilOilStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_increases_stock(): void
    {
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'current_stock_qty' => 10,
            'status' => true,
        ]);

        MobilOilStockService::increment($product, 5);

        $this->assertSame(15.0, (float) $product->fresh()->current_stock_qty);
    }

    public function test_decrement_reduces_stock(): void
    {
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'current_stock_qty' => 10,
            'status' => true,
        ]);

        MobilOilStockService::decrement($product, 3);

        $this->assertSame(7.0, (float) $product->fresh()->current_stock_qty);
    }

    public function test_decrement_throws_when_insufficient_stock(): void
    {
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'current_stock_qty' => 2,
            'status' => true,
        ]);

        $this->expectException(\RuntimeException::class);

        MobilOilStockService::decrement($product, 5);
    }

    public function test_can_decrement_checks_availability(): void
    {
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'current_stock_qty' => 5,
            'status' => true,
        ]);

        $this->assertTrue(MobilOilStockService::canDecrement($product, 5));
        $this->assertFalse(MobilOilStockService::canDecrement($product, 6));
    }
}
