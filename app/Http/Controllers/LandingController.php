<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing', [
            'stationName' => config('portfolio.station_name'),
            'brand' => config('portfolio.brand'),
            'tagline' => config('portfolio.tagline'),
            'phone' => config('portfolio.phone'),
            'address' => config('portfolio.address'),
            'tuckShopUrl' => config('portfolio.tuck_shop_portal_url'),
        ]);
    }
}
