<?php

namespace Tests\Feature\FuelStation;

use App\Models\TankRefill;
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
        $dieselId = \App\Support\FuelProducts::diesel()->id;

        $response = $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $dieselId,
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

    public function test_refill_can_be_edited_and_stock_adjusted(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 500,
            'purchase_rate' => 250,
            'invoice_no' => 'INV-EDIT',
        ])->assertRedirect(route('tank-refills.index'));

        $refill = TankRefill::firstOrFail();

        $this->actingAs($graph['user'])
            ->get(route('tank-refills.edit', $refill))
            ->assertOk()
            ->assertSee('Edit Tank Refill');

        $this->actingAs($graph['user'])->put(route('tank-refills.update', $refill), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 700,
            'purchase_rate' => 260,
            'invoice_no' => 'INV-EDIT-2',
            'notes' => 'Corrected quantity',
        ])->assertRedirect(route('tank-refills.index'));

        $this->assertEquals(1700, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseHas('tank_refills', [
            'id' => $refill->id,
            'quantity_liters' => 700,
            'purchase_rate' => 260,
            'total_amount' => 182000,
            'invoice_no' => 'INV-EDIT-2',
        ]);
    }

    public function test_refill_edit_rejects_when_stock_too_low_to_reverse(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 500,
            'purchase_rate' => 250,
        ])->assertRedirect(route('tank-refills.index'));

        // Simulate later sales consuming most of the tank, including refill liters.
        $graph['tank']->update(['current_stock_liters' => 100]);

        $refill = TankRefill::firstOrFail();

        $this->actingAs($graph['user'])->from(route('tank-refills.edit', $refill))
            ->put(route('tank-refills.update', $refill), [
                'tank_id' => $graph['tank']->id,
                'product_id' => $graph['product']->id,
                'quantity_liters' => 400,
                'purchase_rate' => 250,
            ])
            ->assertRedirect(route('tank-refills.edit', $refill))
            ->assertSessionHas('error');

        $this->assertEquals(100, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertEquals(500, (float) $refill->fresh()->quantity_liters);
    }

    public function test_refill_can_be_reverted(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 500,
            'purchase_rate' => 250,
            'invoice_no' => 'INV-REV',
        ])->assertRedirect(route('tank-refills.index'));

        $refill = TankRefill::firstOrFail();

        $this->actingAs($graph['user'])
            ->post(route('tank-refills.revert', $refill))
            ->assertRedirect(route('tank-refills.index'))
            ->assertSessionHas('success');

        $this->assertEquals(1000, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseMissing('tank_refills', ['id' => $refill->id]);
    }

    public function test_refill_revert_rejects_when_stock_too_low(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 500,
            'purchase_rate' => 250,
        ])->assertRedirect(route('tank-refills.index'));

        $graph['tank']->update(['current_stock_liters' => 50]);
        $refill = TankRefill::firstOrFail();

        $this->actingAs($graph['user'])
            ->from(route('tank-refills.index'))
            ->post(route('tank-refills.revert', $refill))
            ->assertRedirect(route('tank-refills.index'))
            ->assertSessionHas('error');

        $this->assertEquals(50, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseHas('tank_refills', ['id' => $refill->id]);
    }

    public function test_index_shows_edit_and_revert_actions(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 200,
            'purchase_rate' => 250,
        ])->assertRedirect(route('tank-refills.index'));

        $refill = TankRefill::firstOrFail();

        $this->actingAs($graph['user'])
            ->get(route('tank-refills.index', ['filter' => 'today']))
            ->assertOk()
            ->assertSee(route('tank-refills.edit', $refill), false)
            ->assertSee(route('tank-refills.revert', $refill), false);
    }
}
