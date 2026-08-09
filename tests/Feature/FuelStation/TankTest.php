<?php

namespace Tests\Feature\FuelStation;

use App\Models\Tank;
use App\Support\FuelProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class TankTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_tanks(): void
    {
        $this->get(route('tanks.index'))->assertRedirect(route('login'));
        $this->get(route('tanks.create'))->assertRedirect(route('login'));
    }

    public function test_can_list_and_create_tank(): void
    {
        $user = $this->createOwner();
        FuelProducts::ensure();
        $product = FuelProducts::petrol();

        $this->actingAs($user)->get(route('tanks.index'))->assertOk();
        $this->actingAs($user)->get(route('tanks.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('tanks.store'), [
            'product_id' => $product->id,
            'tank_number' => 'T-NEW-01',
            'capacity_liters' => 8000,
            'current_stock_liters' => 1000,
            'minimum_level' => 200,
            'status' => 1,
        ]);

        $response->assertRedirect(route('tanks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tanks', [
            'tank_number' => 'T-NEW-01',
            'capacity_liters' => 8000,
            'current_stock_liters' => 1000,
        ]);
    }

    public function test_tank_rejects_stock_above_capacity(): void
    {
        $user = $this->createOwner();
        FuelProducts::ensure();
        $product = FuelProducts::petrol();

        $response = $this->actingAs($user)->from(route('tanks.create'))->post(route('tanks.store'), [
            'product_id' => $product->id,
            'tank_number' => 'T-BAD',
            'capacity_liters' => 1000,
            'current_stock_liters' => 1500,
            'minimum_level' => 100,
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('current_stock_liters');
        $this->assertDatabaseMissing('tanks', ['tank_number' => 'T-BAD']);
    }

    public function test_tank_requires_unique_number(): void
    {
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($graph['user'])->from(route('tanks.create'))->post(route('tanks.store'), [
            'product_id' => $graph['product']->id,
            'tank_number' => $graph['tank']->tank_number,
            'capacity_liters' => 5000,
            'current_stock_liters' => 0,
            'minimum_level' => 100,
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('tank_number');
    }

    public function test_can_update_and_delete_tank(): void
    {
        $graph = $this->createFuelStationGraph();
        $tank = $graph['tank'];

        $this->actingAs($graph['user'])->get(route('tanks.edit', $tank))->assertOk();

        $response = $this->actingAs($graph['user'])->put(route('tanks.update', $tank), [
            'product_id' => $tank->product_id,
            'tank_number' => 'T-UPDATED',
            'capacity_liters' => 12000,
            'current_stock_liters' => 2000,
            'minimum_level' => 300,
            'status' => 1,
        ]);

        $response->assertRedirect(route('tanks.index'));
        $this->assertDatabaseHas('tanks', [
            'id' => $tank->id,
            'tank_number' => 'T-UPDATED',
            'capacity_liters' => 12000,
        ]);

        $orphan = Tank::create([
            'product_id' => $graph['product']->id,
            'tank_number' => 'T-DEL',
            'capacity_liters' => 1000,
            'current_stock_liters' => 0,
            'minimum_level' => 50,
            'status' => true,
        ]);

        $this->actingAs($graph['user'])
            ->delete(route('tanks.destroy', $orphan))
            ->assertRedirect(route('tanks.index'));

        $this->assertDatabaseMissing('tanks', ['id' => $orphan->id]);
    }
}
