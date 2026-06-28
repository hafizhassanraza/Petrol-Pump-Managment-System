<?php

namespace Tests\Feature\FuelStation;

use App\Models\MobilOilPrice;
use App\Models\MobilOilProduct;
use App\Models\MobilOilSale;
use Database\Seeders\MobilOilSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class MobilOilModuleTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_mobil_oil_routes(): void
    {
        $this->get(route('mobil-oil.products.index'))->assertRedirect(route('login'));
        $this->get(route('mobil-oil.purchases.index'))->assertRedirect(route('login'));
        $this->get(route('mobil-oil.sales.index'))->assertRedirect(route('login'));
    }

    public function test_owner_can_view_mobil_oil_pages(): void
    {
        $graph = $this->createMobilOilGraph();

        $this->actingAs($graph['user'])->get(route('mobil-oil.products.index'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.products.create'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.purchases.index'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.purchases.create'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.sales.index'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.sales.create'))->assertOk();
        $this->actingAs($graph['user'])->get(route('mobil-oil.products.edit', $graph['product']))->assertOk();
    }

    public function test_can_create_product_with_initial_price(): void
    {
        $user = $this->createOwner();

        $response = $this->actingAs($user)->post(route('mobil-oil.products.store'), [
            'name' => 'Castrol GTX 4L',
            'sku' => 'CAS-GTX-4L',
            'unit' => 'bottle',
            'minimum_level' => 3,
            'price' => 2800,
            'status' => 1,
        ]);

        $response->assertRedirect(route('mobil-oil.products.index'));
        $response->assertSessionHas('success');

        $product = MobilOilProduct::where('sku', 'CAS-GTX-4L')->first();
        $this->assertNotNull($product);
        $this->assertSame(2800.0, (float) $product->latestPrice->price);
    }

    public function test_can_update_product_and_price_from_edit(): void
    {
        $graph = $this->createMobilOilGraph(price: 850);

        $response = $this->actingAs($graph['user'])->put(route('mobil-oil.products.update', $graph['product']), [
            'name' => 'Mobil Super 1L Updated',
            'sku' => 'MOB-TEST-01',
            'unit' => 'bottle',
            'minimum_level' => 8,
            'price' => 900,
            'effective_from' => now()->format('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        $response->assertRedirect(route('mobil-oil.products.index'));
        $graph['product']->refresh();
        $this->assertSame('Mobil Super 1L Updated', $graph['product']->name);
        $this->assertSame(900.0, (float) $graph['product']->latestPrice->price);
        $this->assertSame(2, MobilOilPrice::where('mobil_oil_product_id', $graph['product']->id)->count());
    }

    public function test_purchase_increases_stock_and_records_total(): void
    {
        $graph = $this->createMobilOilGraph(stock: 0);

        $response = $this->actingAs($graph['user'])->post(route('mobil-oil.purchases.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 24,
            'purchase_rate' => 650,
            'invoice_no' => 'MO-INV-001',
        ]);

        $response->assertRedirect(route('mobil-oil.purchases.index'));
        $response->assertSessionHas('success');
        $this->assertSame(24.0, (float) $graph['product']->fresh()->current_stock_qty);
        $this->assertDatabaseHas('mobil_oil_purchases', [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 24,
            'total_amount' => 15600,
        ]);
    }

    public function test_purchase_create_page_shows_total_amount_helper(): void
    {
        $graph = $this->createMobilOilGraph();

        $this->actingAs($graph['user'])
            ->get(route('mobil-oil.purchases.create'))
            ->assertOk()
            ->assertSee('Total Amount (PKR)')
            ->assertSee('totalAmountDisplay');
    }

    public function test_sale_decreases_stock_and_calculates_total_from_price(): void
    {
        $graph = $this->createMobilOilGraph(stock: 50, price: 850);

        $response = $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 2,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('mobil-oil.sales.index'));
        $response->assertSessionHas('success');
        $this->assertSame(48.0, (float) $graph['product']->fresh()->current_stock_qty);
        $this->assertDatabaseHas('mobil_oil_sales', [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 2,
            'unit_price' => 850,
            'total_amount' => 1700,
        ]);
    }

    public function test_sale_uses_manual_unit_price_when_provided(): void
    {
        $graph = $this->createMobilOilGraph(stock: 10, price: 850);

        $response = $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 1,
            'unit_price' => 800,
            'payment_method' => 'online',
        ]);

        $response->assertRedirect(route('mobil-oil.sales.index'));
        $this->assertDatabaseHas('mobil_oil_sales', [
            'mobil_oil_product_id' => $graph['product']->id,
            'unit_price' => 800,
            'total_amount' => 800,
            'payment_method' => 'online',
        ]);
    }

    public function test_sale_rejects_insufficient_stock(): void
    {
        $graph = $this->createMobilOilGraph(stock: 1, price: 850);

        $response = $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 5,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1.0, (float) $graph['product']->fresh()->current_stock_qty);
        $this->assertSame(0, MobilOilSale::count());
    }

    public function test_sale_rejects_when_no_price_set(): void
    {
        $user = $this->createOwner();
        $product = MobilOilProduct::create([
            'name' => 'Unpriced Oil',
            'unit' => 'bottle',
            'current_stock_qty' => 10,
            'status' => true,
        ]);

        $response = $this->actingAs($user)->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $product->id,
            'quantity' => 1,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, MobilOilSale::count());
    }

    public function test_sales_index_shows_period_total(): void
    {
        $graph = $this->createMobilOilGraph(stock: 50, price: 850);

        $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 2,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($graph['user'])
            ->get(route('mobil-oil.sales.index', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('1,700.00');
    }

    public function test_sale_create_page_shows_total_amount_helper(): void
    {
        $graph = $this->createMobilOilGraph(stock: 50, price: 850);

        $this->actingAs($graph['user'])
            ->get(route('mobil-oil.sales.create'))
            ->assertOk()
            ->assertSee('Total Amount (PKR)')
            ->assertSee('totalAmountDisplay');
    }

    public function test_stock_report_includes_mobil_oil_section(): void
    {
        $graph = $this->createMobilOilGraph(stock: 12, price: 850);

        $this->actingAs($graph['user'])
            ->get(route('reports.stock'))
            ->assertOk()
            ->assertSee('Mobil Oil Stock')
            ->assertSee($graph['product']->name);
    }

    public function test_profit_loss_report_includes_mobil_oil_sales_and_cogs(): void
    {
        $graph = $this->createMobilOilGraph(stock: 0, price: 850);

        $this->actingAs($graph['user'])->post(route('mobil-oil.purchases.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 10,
            'purchase_rate' => 600,
        ]);

        $graph['product']->refresh();

        $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 2,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($graph['user'])
            ->get(route('reports.profit-loss', ['filter' => 'today']))
            ->assertOk()
            ->assertSee('Mobil Oil Sales')
            ->assertSee('Mobil Oil Purchase COGS');
    }

    public function test_mobil_oil_sales_report_lists_transactions(): void
    {
        $graph = $this->createMobilOilGraph(stock: 10, price: 850);

        $this->actingAs($graph['user'])->post(route('mobil-oil.sales.store'), [
            'mobil_oil_product_id' => $graph['product']->id,
            'quantity' => 1,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($graph['user'])
            ->get(route('reports.mobil-oil-sales', ['filter' => 'today']))
            ->assertOk()
            ->assertSee($graph['product']->name)
            ->assertSee('850.00');
    }

    public function test_mobil_oil_seeder_creates_products_with_prices(): void
    {
        $user = $this->createOwner();

        (new MobilOilSeeder)->run($user->id);

        $this->assertSame(6, MobilOilProduct::count());
        $this->assertSame(6, MobilOilPrice::count());
        $this->assertNotNull(MobilOilProduct::where('sku', 'MOB-SUP-1L')->first()?->latestPrice);
    }

    public function test_removed_prices_route_returns_not_found(): void
    {
        $graph = $this->createMobilOilGraph();

        $this->actingAs($graph['user'])
            ->get('/mobil-oil/prices')
            ->assertNotFound();
    }
}
