<?php

namespace Tests\Feature\FuelStation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class OwnerFuelUsageTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_owner_usage_decrements_stock_and_updates_meter(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 2000, meterReading: 500);

        $response = $this->actingAs($graph['user'])->post(route('owner-fuel-usages.store'), [
            'nozzle_id' => $graph['nozzle']->id,
            'liters' => 25,
            'person_name' => 'Owner',
        ]);

        $response->assertRedirect(route('owner-fuel-usages.index'));
        $response->assertSessionHas('success');
        $this->assertEquals(1975, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertEquals(525, (float) $graph['nozzle']->fresh()->current_meter_reading);
    }

    public function test_owner_usage_fails_without_price(): void
    {
        $user = $this->createOwner();
        $product = \App\Models\Product::create(['name' => 'Unpriced', 'unit' => 'liter', 'status' => true]);
        $tank = \App\Models\Tank::create([
            'product_id' => $product->id,
            'tank_number' => 'T-X',
            'capacity_liters' => 5000,
            'current_stock_liters' => 1000,
            'minimum_level' => 100,
            'status' => true,
        ]);
        $dispenser = \App\Models\Dispenser::create([
            'dispenser_code' => 'D-X',
            'status' => true,
        ]);
        $nozzle = \App\Models\Nozzle::create([
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'product_id' => $product->id,
            'nozzle_number' => 'N-X',
            'current_meter_reading' => 0,
            'status' => true,
        ]);

        $response = $this->actingAs($user)->post(route('owner-fuel-usages.store'), [
            'nozzle_id' => $nozzle->id,
            'liters' => 10,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_index_shows_usage_recorded_in_current_business_day(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 2000, meterReading: 500);

        $this->actingAs($graph['user'])->post(route('owner-fuel-usages.store'), [
            'nozzle_id' => $graph['nozzle']->id,
            'liters' => 25,
            'person_name' => 'Owner',
        ])->assertRedirect(route('owner-fuel-usages.index'));

        $response = $this->actingAs($graph['user'])->get(route('owner-fuel-usages.index', ['filter' => 'today']));

        $response->assertOk();
        $response->assertSee('Owner');
        $response->assertSee('25.00');
    }

    public function test_update_recalculates_stock_meter_and_amount(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 2000, meterReading: 500, pricePerLiter: 300);

        $this->actingAs($graph['user'])->post(route('owner-fuel-usages.store'), [
            'nozzle_id' => $graph['nozzle']->id,
            'liters' => 25,
            'person_name' => 'Owner',
        ]);

        $usage = \App\Models\OwnerFuelUsage::first();

        $response = $this->actingAs($graph['user'])->put(route('owner-fuel-usages.update', $usage), [
            'nozzle_id' => $graph['nozzle']->id,
            'liters' => 40,
            'person_name' => 'Owner Updated',
        ]);

        $response->assertRedirect(route('owner-fuel-usages.index'));
        $response->assertSessionHas('success');

        $usage->refresh();
        $this->assertEquals(40, (float) $usage->liters);
        $this->assertEquals('Owner Updated', $usage->person_name);
        $this->assertEquals(12000, (float) $usage->total_amount);

        $this->assertEquals(1960, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertEquals(540, (float) $graph['nozzle']->fresh()->current_meter_reading);
    }
}
