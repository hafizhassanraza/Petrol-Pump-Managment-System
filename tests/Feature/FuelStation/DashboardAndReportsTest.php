<?php

namespace Tests\Feature\FuelStation;

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
    }

    public function test_landing_page_is_public(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
