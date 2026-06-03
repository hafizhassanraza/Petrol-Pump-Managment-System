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
    }

    public function test_landing_page_is_public(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
