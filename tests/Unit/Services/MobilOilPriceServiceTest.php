<?php

namespace Tests\Unit\Services;

use App\Models\MobilOilProduct;
use App\Models\User;
use App\Services\MobilOilPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilOilPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_unit_price(): void
    {
        $user = User::factory()->create();
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'status' => true,
        ]);

        MobilOilPriceService::setPrice($product->id, 850.00, now(), $user->id);

        $this->assertSame(850.0, MobilOilPriceService::getUnitPrice($product->id));
        $this->assertDatabaseHas('mobil_oil_prices', [
            'mobil_oil_product_id' => $product->id,
            'price' => 850.00,
        ]);
    }

    public function test_returns_latest_effective_price(): void
    {
        $user = User::factory()->create();
        $product = MobilOilProduct::create([
            'name' => 'Test Oil',
            'unit' => 'bottle',
            'status' => true,
        ]);

        MobilOilPriceService::setPrice($product->id, 800.00, now()->subDays(2), $user->id);
        MobilOilPriceService::setPrice($product->id, 900.00, now()->subDay(), $user->id);

        $this->assertSame(900.0, MobilOilPriceService::getUnitPrice($product->id));
    }

    public function test_returns_null_when_no_price(): void
    {
        $product = MobilOilProduct::create([
            'name' => 'No Price Oil',
            'unit' => 'bottle',
            'status' => true,
        ]);

        $this->assertNull(MobilOilPriceService::getUnitPrice($product->id));
    }
}
