<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\ProductPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_price_per_liter_returns_latest_effective_price(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Diesel', 'unit' => 'liter', 'status' => true]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price' => 250,
            'effective_from' => now()->subDays(2),
            'created_by' => $user->id,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price' => 275.5,
            'effective_from' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $this->assertEquals(275.5, ProductPriceService::getPricePerLiter($product->id));
    }

    public function test_get_price_per_liter_returns_null_when_no_price(): void
    {
        $product = Product::create(['name' => 'Empty', 'unit' => 'liter', 'status' => true]);

        $this->assertNull(ProductPriceService::getPricePerLiter($product->id));
    }

    public function test_set_price_creates_row(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Petrol', 'unit' => 'liter', 'status' => true]);

        $row = ProductPriceService::setPrice($product->id, 381.25, now(), $user->id);

        $this->assertInstanceOf(ProductPrice::class, $row);
        $this->assertEquals(381.25, (float) $row->price);
        $this->assertEquals($user->id, $row->created_by);
    }
}
