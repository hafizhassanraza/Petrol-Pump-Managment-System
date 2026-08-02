<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class EmployeeShiftTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_shift_pages(): void
    {
        $this->get(route('employee-shifts.index'))->assertRedirect(route('login'));
        $this->get(route('employee-shifts.create'))->assertRedirect(route('login'));
    }

    public function test_owner_can_assign_shift(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(meterReading: 1000);

        $response = $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $response->assertRedirect(route('employee-shifts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'shift_id' => $graph['shift']->id,
            'opening_reading' => 1000,
            'status' => 'active',
        ]);
    }

    public function test_cannot_assign_second_active_shift_on_same_nozzle_same_day(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph();

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $response = $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, EmployeeShift::count());
    }

    public function test_opening_reading_cannot_be_below_meter(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(meterReading: 2000);

        $response = $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1500,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('employee_shifts', 0);
    }

    public function test_owner_can_close_and_verify_shift(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10000, pricePerLiter: 100, meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $close = $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 10,
            'cash_received' => 8500,
            'online_received' => 500,
        ]);

        $close->assertRedirect(route('employee-shifts.index'));
        $close->assertSessionHas('success');

        $shift->refresh();
        $this->assertSame('submitted', $shift->status);
        $this->assertEquals(90, (float) $shift->total_liters);
        $this->assertEquals(9000, (float) $shift->total_amount);
        $this->assertEquals(8500, (float) $shift->cash_received);
        $this->assertEquals(500, (float) $shift->online_received);
        $this->assertEquals(1100, (float) $graph['nozzle']->fresh()->current_meter_reading);
        $this->assertEquals(9910, (float) $graph['tank']->fresh()->current_stock_liters);

        $verify = $this->actingAs($graph['user'])->post(route('employee-shifts.verify', $shift->id));
        $verify->assertSessionHas('success');
        $this->assertSame('verified', $shift->fresh()->status);
    }

    public function test_close_fails_without_stock(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10, pricePerLiter: 100, meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $response = $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 0,
            'cash_received' => 10000,
            'online_received' => 0,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('active', $shift->fresh()->status);
    }

    public function test_close_form_shows_expected_amount_calculator(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10000, pricePerLiter: 100, meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])
            ->get(route('employee-shifts.close-form', $shift->id))
            ->assertOk()
            ->assertSee('Expected Amount')
            ->assertSee('expectedAmountDisplay')
            ->assertSee('closingReadingInput');
    }

    public function test_can_edit_active_shift_opening_reading(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $response = $this->actingAs($graph['user'])->put(route('employee-shifts.update', $shift->id), [
            'employee_id' => $graph['employee']->id,
            'opening_reading' => 1050,
        ]);

        $response->assertRedirect(route('employee-shifts.index'));
        $this->assertSame(1050.0, (float) $shift->fresh()->opening_reading);
    }

    public function test_can_edit_submitted_shift_with_recalculation(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10000, pricePerLiter: 100, meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 10,
            'cash_received' => 8500,
            'online_received' => 500,
        ]);

        $shift->refresh();
        $this->assertEquals(9910.0, (float) $graph['tank']->fresh()->current_stock_liters);

        $response = $this->actingAs($graph['user'])->put(route('employee-shifts.update', $shift->id), [
            'employee_id' => $graph['employee']->id,
            'closing_reading' => 1120,
            'testing_liters' => 10,
            'cash_received' => 10000,
            'online_received' => 1000,
        ]);

        $response->assertRedirect(route('employee-shifts.index'));
        $shift->refresh();
        $this->assertSame('submitted', $shift->status);
        $this->assertEquals(110.0, (float) $shift->total_liters);
        $this->assertEquals(11000.0, (float) $shift->total_amount);
        $this->assertEquals(1120.0, (float) $graph['nozzle']->fresh()->current_meter_reading);
        $this->assertEquals(9890.0, (float) $graph['tank']->fresh()->current_stock_liters);
    }

    public function test_cannot_edit_verified_shift(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10000, pricePerLiter: 100, meterReading: 1000);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 10,
            'cash_received' => 8500,
            'online_received' => 500,
        ]);

        $this->actingAs($graph['user'])->post(route('employee-shifts.verify', $shift->id));

        $this->actingAs($graph['user'])
            ->get(route('employee-shifts.edit', $shift->id))
            ->assertRedirect(route('employee-shifts.index'));
    }
}
