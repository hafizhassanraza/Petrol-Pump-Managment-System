<?php

namespace Tests\Feature\FuelStation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_report_pdf_and_csv_exports_download(): void
    {
        $graph = $this->createFuelStationGraph();
        $user = $graph['user'];

        $exports = [
            'reports.daily-sales.pdf',
            'reports.daily-sales.csv',
            'reports.profit-loss.pdf',
            'reports.profit-loss.csv',
            'reports.stock.pdf',
            'reports.stock.csv',
            'reports.expenses.pdf',
            'reports.expenses.csv',
            'reports.variance.pdf',
            'reports.variance.csv',
            'reports.attendance.pdf',
            'reports.attendance.csv',
            'reports.mobil-oil-sales.pdf',
            'reports.mobil-oil-sales.csv',
            'reports.cash.pdf',
            'reports.cash.csv',
            'reports.purchases.pdf',
            'reports.purchases.csv',
            'reports.shifts.pdf',
            'reports.shifts.csv',
        ];

        foreach ($exports as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
