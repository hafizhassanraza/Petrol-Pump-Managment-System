<?php

namespace Tests\Feature\FuelStation;

use App\Models\TankDipReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class TankDipReadingTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_dip_reading_appears_in_index_with_today_filter_before_nine_am(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 5000);
        $this->travelTo(now()->setTime(8, 30, 0));

        $this->actingAs($graph['user'])->post(route('tank-dip-readings.store'), [
            'tank_id' => $graph['tank']->id,
            'measured_liters' => 4950,
        ])->assertRedirect(route('tank-dip-readings.index'));

        $this->assertDatabaseHas('tank_dip_readings', [
            'tank_id' => $graph['tank']->id,
            'measured_liters' => 4950,
        ]);

        $this->actingAs($graph['user'])
            ->get(route('tank-dip-readings.index', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('4,950.00');
    }

    public function test_dip_reading_appears_in_index_with_today_filter_during_business_hours(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 5000);
        $this->travelTo(now()->setTime(14, 0, 0));

        $this->actingAs($graph['user'])->post(route('tank-dip-readings.store'), [
            'tank_id' => $graph['tank']->id,
            'measured_liters' => 5100,
        ])->assertRedirect(route('tank-dip-readings.index'));

        $this->actingAs($graph['user'])
            ->get(route('tank-dip-readings.index', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('5,100.00');
    }

    public function test_dip_reading_can_reconcile_stock(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 5000);
        $this->travelTo(now()->setTime(10, 0, 0));

        $this->actingAs($graph['user'])->post(route('tank-dip-readings.store'), [
            'tank_id' => $graph['tank']->id,
            'measured_liters' => 4800,
            'reconcile_stock' => 1,
        ])->assertRedirect(route('tank-dip-readings.index'));

        $this->assertSame(4800.0, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertTrue(
            TankDipReading::where('tank_id', $graph['tank']->id)->value('stock_reconciled')
        );
    }

    public function test_dip_reading_stores_variance_columns(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 5000);
        $this->travelTo(now()->setTime(11, 0, 0));

        $this->actingAs($graph['user'])->post(route('tank-dip-readings.store'), [
            'tank_id' => $graph['tank']->id,
            'measured_liters' => 4900,
        ]);

        $reading = TankDipReading::first();
        $this->assertNotNull($reading);
        $this->assertSame(5000.0, (float) $reading->system_stock_liters);
        $this->assertSame(-100.0, (float) $reading->difference_liters);
    }
}
