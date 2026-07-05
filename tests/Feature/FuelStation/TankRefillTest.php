<?php

namespace Tests\Feature\FuelStation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class TankRefillTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_refill_increases_tank_stock(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $response = $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 500,
            'purchase_rate' => 250,
            'invoice_no' => 'INV-001',
        ]);

        $response->assertRedirect(route('tank-refills.index'));
        $response->assertSessionHas('success');
        $this->assertEquals(1500, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseHas('tank_refills', [
            'tank_id' => $graph['tank']->id,
            'quantity_liters' => 500,
            'stock_before_liters' => 1000,
        ]);
    }

    public function test_refill_rejects_product_tank_mismatch(): void
    {
        $graph = $this->createFuelStationGraph();

        $other = \App\Models\Product::create(['name' => 'Other', 'unit' => 'liter', 'status' => true]);

        $response = $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $other->id,
            'quantity_liters' => 100,
            'purchase_rate' => 250,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(5000, (float) $graph['tank']->fresh()->current_stock_liters);
    }

    public function test_refill_rejects_over_capacity(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 9500);

        $response = $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 1000,
            'purchase_rate' => 250,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(9500, (float) $graph['tank']->fresh()->current_stock_liters);
    }
}
