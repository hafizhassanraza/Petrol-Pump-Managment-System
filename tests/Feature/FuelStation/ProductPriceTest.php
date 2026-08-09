<?php

namespace Tests\Feature\FuelStation;

use App\Support\FuelProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class ProductPriceTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_product_prices(): void
    {
        $this->get(route('product-prices.index'))->assertRedirect(route('login'));
        $this->get(route('product-prices.create'))->assertRedirect(route('login'));
    }

    public function test_can_list_and_set_product_price(): void
    {
        $user = $this->createOwner();
        FuelProducts::ensure();
        $product = FuelProducts::petrol();

        $this->actingAs($user)->get(route('product-prices.index'))->assertOk();
        $this->actingAs($user)->get(route('product-prices.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('product-prices.store'), [
            'product_id' => $product->id,
            'price' => 275.5,
            'effective_from' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('product-prices.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'price' => 275.5,
        ]);
    }

    public function test_product_price_validation_rejects_invalid_input(): void
    {
        $user = $this->createOwner();
        FuelProducts::ensure();

        $response = $this->actingAs($user)->from(route('product-prices.create'))->post(route('product-prices.store'), [
            'product_id' => 999999,
            'price' => 0,
            'effective_from' => 'not-a-date',
        ]);

        $response->assertSessionHasErrors(['product_id', 'price', 'effective_from']);
    }
}
