<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_authenticated_owner_can_view_dashboard(): void
    {
        $graph = $this->createFuelStationGraph();

        $this->actingAs($graph['user'])
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($graph['user'])
            ->get(route('dashboard', ['filter' => 'last-week']))
            ->assertOk()
            ->assertSee('Last 7 days');

        $this->actingAs($graph['user'])
            ->get(route('dashboard', ['filter' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk();
    }

    public function test_authenticated_owner_can_view_reports(): void
    {
        $graph = $this->createFuelStationGraph();

        $user = $graph['user'];

        $this->actingAs($user)->get(route('reports.daily-sales'))->assertOk();
        $this->actingAs($user)->get(route('reports.profit-loss'))->assertOk();
        $this->actingAs($user)->get(route('reports.stock'))->assertOk();
        $this->actingAs($user)->get(route('reports.expenses'))->assertOk();
        $this->actingAs($user)->get(route('reports.variance'))->assertOk();
        $this->actingAs($user)->get(route('reports.attendance'))->assertOk();
        $this->actingAs($user)->get(route('reports.mobil-oil-sales'))->assertOk();
        $this->actingAs($user)->get(route('reports.cash'))->assertOk();
        $this->actingAs($user)->get(route('reports.purchases'))->assertOk();
        $this->actingAs($user)->get(route('reports.shifts'))->assertOk();
    }

    public function test_landing_page_is_public(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_dashboard_and_daily_sales_show_cash_online_split(): void
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
        ])->assertRedirect(route('employee-shifts.index'));

        $this->actingAs($graph['user'])
            ->get(route('employee-shifts.index', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Cash')
            ->assertSee('Online')
            ->assertSee('8,500')
            ->assertSee('500');

        $this->actingAs($graph['user'])
            ->get(route('reports.daily-sales', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Cash Received')
            ->assertSee('Online Received')
            ->assertSee('Petroleum Sales')
            ->assertSee('Daily Breakdown')
            ->assertDontSee('>Employee</th>')
            ->assertDontSee('Nozzle #')
            ->assertSee('8,500')
            ->assertSee('500');

        $this->actingAs($graph['user'])
            ->get(route('dashboard', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Cash Received')
            ->assertSee('Online Received')
            ->assertSee('8,500')
            ->assertSee('500');

        $csv = $this->actingAs($graph['user'])
            ->get(route('reports.daily-sales.csv', ['filter' => 'today']));

        $csv->assertOk();
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Cash', $csvContent);
        $this->assertStringContainsString('Online', $csvContent);
        $this->assertStringContainsString('8,500', $csvContent);
        $this->assertStringContainsString('500', $csvContent);
    }

    public function test_cash_and_purchase_reports_show_period_data(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 0, pricePerLiter: 210, meterReading: 1000);
        $businessDate = \App\Services\BusinessDayService::currentBusinessDate()->toDateString();

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 100,
            'purchase_rate' => 200,
            'invoice_no' => 'INV-CASH-1',
        ])->assertRedirect();

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'opening_reading' => 1000,
        ]);

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closing_reading' => 1070,
            'testing_liters' => 0,
            'cash_received' => 14700,
            'online_received' => 300,
        ])->assertRedirect();

        $this->actingAs($graph['user'])->post(route('cash-transactions.store'), [
            'type' => 'cash_in',
            'category' => 'Owner Investment',
            'amount' => 5000,
            'transaction_date' => $businessDate,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->actingAs($graph['user'])->post(route('expenses.store'), [
            'expense_type' => 'Utilities',
            'amount' => 1200,
            'expense_date' => $businessDate,
            'notes' => 'Electricity',
        ])->assertRedirect();

        $this->actingAs($graph['user'])
            ->get(route('reports.cash', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Daily Cash Ledger')
            ->assertSee('Closing')
            ->assertSee('Sales Cash')
            ->assertSee('Cash In')
            ->assertSee('Expenses')
            ->assertDontSee('>Opening</th>')
            ->assertSee('14,700')
            ->assertSee('5,000')
            ->assertSee('1,200')
            ->assertSee('18,500'); // 0 + 14700 + 5000 - 1200

        $this->actingAs($graph['user'])
            ->get(route('reports.purchases', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Petroleum Purchases')
            ->assertSee('Mobil Oil Purchases')
            ->assertSee('INV-CASH-1')
            ->assertSee('20,000');
    }

    public function test_shift_report_shows_closed_shift_details(): void
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
        ])->assertRedirect();

        $this->actingAs($graph['user'])
            ->get(route('reports.shifts', ['filter' => 'today']))
            ->assertOk()
            ->assertSee($graph['employee']->name)
            ->assertSee('8,500')
            ->assertSee('500')
            ->assertSee('90.00');
    }
}
