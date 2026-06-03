<?php

namespace Tests\Feature\FuelStation;

use App\Models\Product;
use App\Models\Tank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class NozzleTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_nozzle_requires_matching_tank_product(): void
    {
        $user = $this->createOwner();
        $graph = $this->createFuelStationGraph();

        $diesel = Product::create(['name' => 'Diesel', 'unit' => 'liter', 'status' => true]);
        $dieselTank = Tank::create([
            'product_id' => $diesel->id,
            'tank_number' => 'T-DSL',
            'capacity_liters' => 5000,
            'current_stock_liters' => 0,
            'minimum_level' => 100,
            'status' => true,
        ]);

        $response = $this->actingAs($user)->post(route('nozzles.store'), [
            'dispenser_id' => $graph['dispenser']->id,
            'tank_id' => $dieselTank->id,
            'product_id' => $graph['product']->id,
            'nozzle_number' => 'N-BAD',
            'current_meter_reading' => 0,
            'status' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('nozzles', ['nozzle_number' => 'N-BAD']);
    }

    public function test_nozzle_created_when_products_match(): void
    {
        $user = $this->createOwner();
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($user)->post(route('nozzles.store'), [
            'dispenser_id' => $graph['dispenser']->id,
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'nozzle_number' => 'N-NEW',
            'current_meter_reading' => 50,
            'status' => 1,
        ]);

        $response->assertRedirect(route('nozzles.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('nozzles', ['nozzle_number' => 'N-NEW']);
    }
}
