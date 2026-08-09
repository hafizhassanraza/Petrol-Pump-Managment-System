<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeShift;
use App\Support\DailyFuelMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class DailyFuelMetricsReportTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_daily_metrics_compute_rates_profit_and_closing_balance(): void
    {
        $this->travelToBusinessHours();

        $graph = $this->createFuelStationGraph(
            tankStock: 0,
            pricePerLiter: 210,
            meterReading: 1000,
        );

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 100,
            'purchase_rate' => 200,
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
            'online_received' => 0,
        ])->assertRedirect();

        $date = $shift->fresh()->closed_date;
        $dateStr = \Carbon\Carbon::parse($date)->format('Y-m-d');

        $metrics = DailyFuelMetrics::forDates([$dateStr])->get($dateStr);

        $this->assertEquals(200.0, $metrics['purchase_rate']);
        $this->assertEquals(210.0, $metrics['sale_rate']);
        $this->assertEquals(10.0, $metrics['profit_per_liter']);
        $this->assertEquals(700.0, $metrics['total_profit']);
        $this->assertEquals(30.0, $metrics['closing_stock_liters']);
        $this->assertEquals(6000.0, $metrics['closing_balance']);
    }

    public function test_product_wise_sales_and_profit(): void
    {
        $this->travelToBusinessHours();

        $graph = $this->createFuelStationGraph(
            tankStock: 0,
            pricePerLiter: 210,
            meterReading: 1000,
        );

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 100,
            'purchase_rate' => 200,
        ]);

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
            'online_received' => 0,
        ]);

        $dateStr = \Carbon\Carbon::parse($shift->fresh()->closed_date)->format('Y-m-d');
        $byProduct = DailyFuelMetrics::byProduct($dateStr, $dateStr);

        $this->assertCount(2, $byProduct);
        $this->assertArrayHasKey('petrol', $byProduct->all());
        $this->assertArrayHasKey('diesel', $byProduct->all());

        $row = $byProduct['petrol'];
        $this->assertEquals('Petrol', $row['product']);
        $this->assertEquals(70.0, $row['liters']);
        $this->assertEquals(14700.0, $row['sales_amount']);
        $this->assertEquals(200.0, $row['purchase_rate']);
        $this->assertEquals(210.0, $row['sale_rate']);
        $this->assertEquals(10.0, $row['profit_per_liter']);
        $this->assertEquals(700.0, $row['total_profit']);
        $this->assertEquals(30.0, $row['closing_stock_liters']);
        $this->assertEquals(6000.0, $row['closing_balance']);
    }

    public function test_daily_sales_and_profit_loss_show_new_breakdown_columns(): void
    {
        $this->travelToBusinessHours();

        $graph = $this->createFuelStationGraph(
            tankStock: 0,
            pricePerLiter: 210,
            meterReading: 1000,
        );

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 100,
            'purchase_rate' => 200,
        ]);

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
            'online_received' => 0,
        ]);

        $this->actingAs($graph['user'])
            ->get(route('reports.daily-sales', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Daily Breakdown')
            ->assertSee('Petroleum Sales')
            ->assertSee('Petrol')
            ->assertSee('Diesel')
            ->assertSee('Mobil Oil')
            ->assertSee('Bank')
            ->assertSee('210.00 × 70.00')
            ->assertSee('14,700')
            ->assertSee('Profit 700')
            ->assertSee('Close')
            ->assertDontSee('Open ')
            ->assertSee('Stock refill')
            ->assertSee('Sale price set')
            ->assertSee('+100.00 L')
            ->assertSee('purchase 200.00');

        $this->travel(1)->minutes();

        $this->actingAs($graph['user'])
            ->post(route('product-prices.store'), [
                'product_id' => $graph['product']->id,
                'price' => 270,
                'effective_from' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->actingAs($graph['user'])
            ->get(route('reports.daily-sales', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Price change')
            ->assertSee('210.00 → 270.00');

        $this->actingAs($graph['user'])
            ->get(route('reports.profit-loss', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Petroleum Sales')
            ->assertSee('Total Sales & Profit')
            ->assertDontSee('Purchasing (Petroleum)')
            ->assertDontSee('P&L Summary')
            ->assertDontSee('Daily Breakdown')
            ->assertSee('Total Profit')
            ->assertSee('700');
    }

    public function test_sales_metrics_use_closing_date_not_opening_date(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 10000, pricePerLiter: 100, meterReading: 1000);

        $openingDate = now()->subDays(2)->toDateString();
        $closingDate = now()->toDateString();

        $this->actingAs($graph['user'])->post(route('employee-shifts.store'), [
            'employee_id' => $graph['employee']->id,
            'nozzle_id' => $graph['nozzle']->id,
            'assigned_date' => $openingDate,
            'opening_reading' => 1000,
        ])->assertRedirect();

        $shift = EmployeeShift::first();

        $this->actingAs($graph['user'])->post(route('employee-shifts.close', $shift->id), [
            'closed_date' => $closingDate,
            'closing_reading' => 1050,
            'testing_liters' => 0,
            'cash_received' => 5000,
            'online_received' => 0,
        ])->assertRedirect();

        $metricsOnOpen = DailyFuelMetrics::forDates([$openingDate])->get($openingDate);
        $metricsOnClose = DailyFuelMetrics::forDates([$closingDate])->get($closingDate);

        $this->assertNull($metricsOnOpen['sale_rate'] ?? null);
        $this->assertEquals(100.0, $metricsOnClose['sale_rate']);
        $this->assertEquals(50.0, (float) DailyFuelMetrics::byProduct($closingDate, $closingDate)['petrol']['liters']);
        $this->assertEquals(0.0, (float) DailyFuelMetrics::byProduct($openingDate, $openingDate)['petrol']['liters']);

        $cashOpen = \App\Support\DailyCashLedger::forRange($openingDate, $openingDate);
        $cashClose = \App\Support\DailyCashLedger::forRange($closingDate, $closingDate);

        $this->assertEquals(0.0, $cashOpen['total_sales_cash']);
        $this->assertEquals(5000.0, $cashClose['total_sales_cash']);
    }
}
