<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeShift;
use App\Models\OwnerFuelUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class OwnerFuelUsageTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_owner_fuel_is_created_from_shift_close(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 2000, meterReading: 500, pricePerLiter: 300);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 500,
        ])->assertRedirect();

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 560,
            'testing_liters' => 0,
            'cash_received' => 15000,
            'online_received' => 0,
            'has_owner_fuel' => 1,
            'owner_fuel_liters' => 10,
            'owner_person_name' => 'Owner Car',
            'owner_vehicle_no' => 'ABC-123',
        ])->assertRedirect(route('employee-shifts.index'));

        $shift->refresh();
        $usage = OwnerFuelUsage::first();

        $this->assertEquals(50.0, (float) $shift->total_liters);
        $this->assertEquals(15000.0, (float) $shift->total_amount);
        $this->assertNotNull($usage);
        $this->assertEquals($shift->id, $usage->employee_shift_id);
        $this->assertEquals(10.0, (float) $usage->liters);
        $this->assertEquals(1940.0, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertEquals(560.0, (float) $graph['nozzle']->fresh()->current_meter_reading);
    }

    public function test_owner_fuel_edit_routes_removed(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('owner-fuel-usages.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('owner-fuel-usages.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('owner-fuel-usages.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('owner-fuel-usages.update'));

        $graph = $this->createFuelStationGraph();

        $this->actingAs($graph['user'])
            ->get(route('owner-fuel-usages.index'))
            ->assertOk()
            ->assertDontSee('Add Usage')
            ->assertDontSee('>Edit</a>', false);
    }

    public function test_owner_fuel_is_not_double_counted_in_dashboard_and_profit_loss(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 2000, meterReading: 500, pricePerLiter: 300);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 500,
        ])->assertRedirect();

        $shift = EmployeeShift::first();
        $closedDate = \App\Services\BusinessDayService::currentBusinessDate()->toDateString();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 560,
            'testing_liters' => 0,
            'cash_received' => 15000,
            'online_received' => 0,
            'closed_date' => $closedDate,
            'has_owner_fuel' => 1,
            'owner_fuel_liters' => 10,
            'owner_person_name' => 'Owner Car',
        ])->assertRedirect();

        $this->actingAs($graph['user'])->post(route('expenses.store'), [
            'expense_type' => 'Miscellaneous',
            'amount' => 1000,
            'expense_date' => $closedDate,
        ])->assertRedirect();

        $shift->refresh();
        $this->assertEquals(50.0, (float) $shift->total_liters);
        $this->assertEquals(15000.0, (float) $shift->total_amount);
        $this->assertEquals(3000.0, (float) OwnerFuelUsage::sum('total_amount'));

        // Net = sales 15000 - expense 1000 = 14000 (owner fuel must NOT be deducted again).
        $dashboard = $this->actingAs($graph['user'])->get(route('dashboard', [
            'filter' => 'custom',
            'from' => $closedDate,
            'to' => $closedDate,
        ]));
        $dashboard->assertOk();
        $dashboard->assertSee('excluded from sales', false);
        $dashboard->assertSee('15000', false);
        $dashboard->assertSee('14000', false);

        $pl = $this->actingAs($graph['user'])->get(route('reports.profit-loss', [
            'filter' => 'custom',
            'from' => $closedDate,
            'to' => $closedDate,
        ]));
        $pl->assertOk();
        $pl->assertSee('Already excluded from sales', false);
        $pl->assertDontSee('- 3,000', false);
    }
}
