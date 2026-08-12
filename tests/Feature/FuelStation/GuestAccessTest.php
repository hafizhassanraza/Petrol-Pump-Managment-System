<?php

namespace Tests\Feature\FuelStation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            ['dashboard'],
            ['tanks.index'],
            ['dispensers.index'],
            ['nozzles.index'],
            ['employees.index'],
            ['employee-attendances.index'],
            ['employee-salaries.index'],
            ['employee-shifts.index'],
            ['tank-refills.index'],
            ['tank-dip-readings.index'],
            ['product-prices.index'],
            ['expenses.index'],
            ['cash-transactions.index'],
            ['owner-fuel-usages.index'],
            ['agency-customers.index'],
            ['audit-logs.index'],
            ['mobil-oil.products.index'],
            ['mobil-oil.purchases.index'],
            ['mobil-oil.sales.index'],
            ['reports.dashboard'],
            ['reports.daily-sales'],
            ['reports.profit-loss'],
            ['reports.stock'],
            ['reports.expenses'],
            ['reports.variance'],
            ['reports.attendance'],
            ['reports.mobil-oil-sales'],
            ['reports.cash'],
            ['reports.purchases'],
            ['reports.shifts'],
            ['profile.edit'],
        ];
    }

    #[DataProvider('protectedRoutesProvider')]
    public function test_guest_is_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }
}
