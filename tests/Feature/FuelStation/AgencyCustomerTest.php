<?php

namespace Tests\Feature\FuelStation;

use App\Models\AgencyCustomer;
use App\Models\AgencyFuelCredit;
use App\Models\EmployeeShift;
use App\Models\OwnerFuelUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class AgencyCustomerTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_can_create_agency_customer(): void
    {
        $graph = $this->createFuelStationGraph();

        $this->actingAs($graph['user'])->post(route('agency-customers.store'), [
            'name' => 'City Transport',
            'phone' => '03001234567',
            'status' => 1,
        ])->assertRedirect(route('agency-customers.index'));

        $this->assertDatabaseHas('agency_customers', [
            'name' => 'City Transport',
            'phone' => '03001234567',
        ]);
    }

    public function test_agency_credit_created_from_shift_close_and_can_pay_installments(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 5000, meterReading: 1000, pricePerLiter: 280);

        $customer = AgencyCustomer::create([
            'name' => 'Agency One',
            'status' => true,
        ]);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 0,
            'cash_received' => 19600,
            'online_received' => 0,
            'has_agency_fuel' => 1,
            'agency_customer_id' => $customer->id,
            'agency_fuel_liters' => 30,
            'agency_sale_price' => 250,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift->refresh();
        $credit = AgencyFuelCredit::first();

        // Gross 100 − agency 30 = 70 L sold @ 280 = 19,600
        $this->assertEquals(70.0, (float) $shift->total_liters);
        $this->assertEquals(19600.0, (float) $shift->total_amount);
        $this->assertNotNull($credit);
        $this->assertEquals(30.0, (float) $credit->liters);
        $this->assertEquals(250.0, (float) $credit->price_per_liter);
        $this->assertEquals(7500.0, (float) $credit->total_amount); // 30 × 250
        $this->assertEquals('open', $credit->status);
        $this->assertEquals(4900.0, (float) $graph['tank']->fresh()->current_stock_liters);

        $this->actingAs($graph['user'])->post(route('agency-customers.credits.pay', $credit), [
            'amount' => 4000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect();

        $credit->refresh();
        $this->assertEquals('partial', $credit->status);
        $this->assertEquals(4000.0, (float) $credit->paid_amount);
        $this->assertEquals(3500.0, $credit->balance());

        $this->actingAs($graph['user'])->post(route('agency-customers.credits.pay', $credit), [
            'amount' => 3500,
            'payment_method' => 'online',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect();

        $credit->refresh();
        $this->assertEquals('paid', $credit->status);
        $this->assertEquals(0.0, $credit->balance());
    }

    public function test_shift_edit_can_update_owner_and_agency_fuel(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 5000, meterReading: 1000, pricePerLiter: 200);

        $customer = AgencyCustomer::create(['name' => 'Agency Two', 'status' => true]);

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1100,
            'testing_liters' => 0,
            'cash_received' => 14000,
            'online_received' => 0,
            'has_owner_fuel' => 1,
            'owner_fuel_liters' => 10,
            'has_agency_fuel' => 1,
            'agency_customer_id' => $customer->id,
            'agency_fuel_liters' => 20,
            'agency_sale_price' => 200,
        ])->assertRedirect();

        $shift->refresh();
        $this->assertEquals(70.0, (float) $shift->total_liters); // 100 - 10 - 20
        $this->assertEquals(10.0, (float) OwnerFuelUsage::first()->liters);
        $this->assertEquals(20.0, (float) AgencyFuelCredit::first()->liters);

        $this->actingAs($graph['user'])->put(route('employee-shifts.update', $shift->id), [
            'employee_id' => $graph['employee']->id,
            'assigned_date' => $shift->assigned_date->toDateString(),
            'closed_date' => $shift->closed_date->toDateString(),
            'closing_reading' => 1100,
            'testing_liters' => 0,
            'cash_received' => 15000,
            'online_received' => 0,
            'has_owner_fuel' => 1,
            'owner_fuel_liters' => 15,
            'owner_person_name' => 'Owner',
            'has_agency_fuel' => 1,
            'agency_customer_id' => $customer->id,
            'agency_fuel_liters' => 10,
            'agency_sale_price' => 210,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift->refresh();
        $this->assertEquals(75.0, (float) $shift->total_liters); // 100 - 15 - 10
        $this->assertEquals(15.0, (float) OwnerFuelUsage::first()->liters);
        $this->assertEquals(10.0, (float) AgencyFuelCredit::first()->liters);
        $this->assertEquals(210.0, (float) AgencyFuelCredit::first()->price_per_liter);
        $this->assertEquals(2100.0, (float) AgencyFuelCredit::first()->total_amount);
        $this->assertEquals(4900.0, (float) $graph['tank']->fresh()->current_stock_liters); // 5000 - 75 - 15 - 10
    }
}
