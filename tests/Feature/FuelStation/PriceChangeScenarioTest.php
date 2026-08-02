<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeShift;
use App\Models\TankRefill;
use App\Services\ProductPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

/**
 * Documents how selling price / purchase rate behave when prices change mid-stock.
 *
 * Scenario:
 * 1) Buy 100 L @ purchase 200, set sale price 210
 * 2) Sell/consume 70 L while price is still 210
 * 3) Change purchase rate to 250 and sale price to 270
 * 4) Sell remaining 30 L at the new sale price
 */
class PriceChangeScenarioTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_price_change_scenario_sales_use_price_at_close_and_cogs_are_purchases(): void
    {
        $this->travelToBusinessHours();

        // Start empty tank so stock matches the story exactly
        $graph = $this->createFuelStationGraph(
            tankStock: 0,
            pricePerLiter: 210,
            meterReading: 1000,
        );

        $user = $graph['user'];
        $tank = $graph['tank'];
        $product = $graph['product'];
        $nozzle = $graph['nozzle'];
        $employee = $graph['employee'];

        // --- Step 1: Store 100 L @ 200 PKR purchase (COGS invoice) ---
        $this->actingAs($user)->post(route('tank-refills.store'), [
            'tank_id' => $tank->id,
            'product_id' => $product->id,
            'quantity_liters' => 100,
            'purchase_rate' => 200,
            'invoice_no' => 'INV-OLD',
        ])->assertRedirect(route('tank-refills.index'));

        $this->assertEquals(100, (float) $tank->fresh()->current_stock_liters);
        $this->assertEquals(210, ProductPriceService::getPricePerLiter($product->id));

        $firstPurchase = TankRefill::first();
        $this->assertEquals(200, (float) $firstPurchase->purchase_rate);
        $this->assertEquals(20000, (float) $firstPurchase->total_amount); // 100 × 200

        // --- Step 2: Consume 70 L while sale price is still 210 ---
        $this->actingAs($user)->post(route('employee-shifts.store'), [
            'employee_id' => $employee->id,
            'nozzle_id' => $nozzle->id,
            'opening_reading' => 1000,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift1 = EmployeeShift::first();

        $this->actingAs($user)->post(route('employee-shifts.close', $shift1->id), [
            'closing_reading' => 1070, // 70 L gross, 0 testing
            'testing_liters' => 0,
            'cash_received' => 14700, // 70 × 210
            'online_received' => 0,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift1->refresh();
        $this->assertEquals(70, (float) $shift1->total_liters);
        $this->assertEquals(210, (float) $shift1->price_per_liter);
        $this->assertEquals(14700, (float) $shift1->total_amount); // 70 × 210
        $this->assertEquals(30, (float) $tank->fresh()->current_stock_liters);

        // --- Step 3: Prices change — sale 270, and a new purchase at 250 ---
        ProductPriceService::setPrice($product->id, 270, now(), $user->id);
        $this->assertEquals(270, ProductPriceService::getPricePerLiter($product->id));

        // Old 70 L sale is NOT recalculated — still locked at 210
        $this->assertEquals(14700, (float) $shift1->fresh()->total_amount);
        $this->assertEquals(210, (float) $shift1->fresh()->price_per_liter);

        // Remaining 30 L in tank still have NO per-liter inventory cost stored.
        // A new purchase at 250 only records that invoice; it does not revalue old stock.
        $this->actingAs($user)->post(route('tank-refills.store'), [
            'tank_id' => $tank->id,
            'product_id' => $product->id,
            'quantity_liters' => 50,
            'purchase_rate' => 250,
            'invoice_no' => 'INV-NEW',
        ])->assertRedirect(route('tank-refills.index'));

        $this->assertEquals(80, (float) $tank->fresh()->current_stock_liters); // 30 old + 50 new
        $this->assertEquals(12500, (float) TankRefill::where('invoice_no', 'INV-NEW')->value('total_amount')); // 50 × 250

        // --- Step 4: Sell 30 L after price change → billed at NEW sale price 270 ---
        $this->actingAs($user)->post(route('employee-shifts.store'), [
            'employee_id' => $employee->id,
            'nozzle_id' => $nozzle->id,
            'opening_reading' => 1070,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift2 = EmployeeShift::orderByDesc('id')->first();

        $this->actingAs($user)->post(route('employee-shifts.close', $shift2->id), [
            'closing_reading' => 1100, // +30 L
            'testing_liters' => 0,
            'cash_received' => 8100, // 30 × 270
            'online_received' => 0,
        ])->assertRedirect(route('employee-shifts.index'));

        $shift2->refresh();
        $this->assertEquals(30, (float) $shift2->total_liters);
        $this->assertEquals(270, (float) $shift2->price_per_liter);
        $this->assertEquals(8100, (float) $shift2->total_amount); // 30 × 270
        $this->assertEquals(50, (float) $tank->fresh()->current_stock_liters);

        // --- Reporting: sales = sum of shift amounts; "COGS" = sum of purchase invoices ---
        $this->actingAs($user)
            ->get(route('reports.profit-loss', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('22,800')  // fuel sales: 14700 + 8100
            ->assertSee('32,500'); // refill purchases: 20000 + 12500

        // True inventory COGS for 100 L sold would be different (FIFO/WAC) — system does NOT do that.
        // What system reports as Tank Refill COGS = purchases received in the period = 32,500.
        $this->assertEquals(22800.0, (float) EmployeeShift::sum('total_amount'));
        $this->assertEquals(32500.0, (float) TankRefill::sum('total_amount'));
    }

    public function test_old_shift_amount_stays_locked_when_sale_price_changes_later(): void
    {
        $this->travelToBusinessHours();
        $graph = $this->createFuelStationGraph(tankStock: 500, pricePerLiter: 210, meterReading: 1000);

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

        ProductPriceService::setPrice($graph['product']->id, 270, now(), $graph['user']->id);

        $shift->refresh();
        $this->assertEquals(210, (float) $shift->price_per_liter);
        $this->assertEquals(14700, (float) $shift->total_amount);
        $this->assertEquals(270, ProductPriceService::getPricePerLiter($graph['product']->id));
    }
}
