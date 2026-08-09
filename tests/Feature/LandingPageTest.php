<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_station_branding_and_portals(): void
    {
        config([
            'portfolio.station_name' => 'Afreen Petroleum',
            'portfolio.brand' => 'Hascol',
            'portfolio.tagline' => 'Quality fuel & convenience under one roof',
            'portfolio.tuck_shop_portal_url' => 'https://tuckshop.example.test/',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Afreen Petroleum');
        $response->assertSee('Hascol');
        $response->assertSee('Quality fuel & convenience under one roof');
        $response->assertSee('Fuel Station Portal');
        $response->assertSee('Tuck Shop Portal');
        $response->assertSee(route('login'), false);
        $response->assertSee('https://tuckshop.example.test/', false);
        $response->assertSee('images/logo.png', false);
    }

    public function test_landing_page_is_public(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get('/')->assertOk();
    }
}
