<?php

namespace Tests\Feature\FuelStation;

use App\Models\Expense;
use App\Models\TankRefill;
use App\Services\DataManagement\DataBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class DataManagementTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_data_management(): void
    {
        $this->get(route('data-management.index'))->assertRedirect(route('login'));
        $this->get(route('data-management.export'))->assertRedirect(route('login'));
    }

    public function test_owner_can_view_data_management_page(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)
            ->get(route('data-management.index'))
            ->assertOk()
            ->assertSee('Data Management')
            ->assertSee('Export Full Backup')
            ->assertSee('Import Backup')
            ->assertSee('Data Revert');
    }

    public function test_export_downloads_json_backup(): void
    {
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($graph['user'])->get(route('data-management.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $json = json_decode($response->streamedContent(), true);

        $this->assertIsArray($json);
        $this->assertSame('fuel-station', $json['meta']['app'] ?? null);
        $this->assertSame(1, $json['meta']['version'] ?? null);
        $this->assertArrayHasKey('tanks', $json['tables']);
        $this->assertNotEmpty($json['tables']['tanks']);
    }

    public function test_import_restores_exported_backup(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 250,
            'purchase_rate' => 200,
            'invoice_no' => 'BACKUP-1',
        ])->assertRedirect(route('tank-refills.index'));

        $payload = app(DataBackupService::class)->export();
        $this->assertGreaterThan(0, TankRefill::count());

        TankRefill::query()->delete();
        $graph['tank']->update(['current_stock_liters' => 1000]);
        $this->assertEquals(0, TankRefill::count());

        $file = UploadedFile::fake()->createWithContent(
            'backup.json',
            json_encode($payload)
        );

        $this->actingAs($graph['user'])
            ->post(route('data-management.import'), [
                'backup_file' => $file,
                'confirm_import' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tank_refills', ['invoice_no' => 'BACKUP-1']);
        $this->assertEquals(1250, (float) $graph['tank']->fresh()->current_stock_liters);
    }

    public function test_revert_removes_data_from_selected_date_and_restores_stock(): void
    {
        $graph = $this->createFuelStationGraph(tankStock: 1000);
        $today = now()->toDateString();

        $this->actingAs($graph['user'])->post(route('tank-refills.store'), [
            'tank_id' => $graph['tank']->id,
            'product_id' => $graph['product']->id,
            'quantity_liters' => 400,
            'purchase_rate' => 220,
            'invoice_no' => 'REV-REFILL',
        ])->assertRedirect(route('tank-refills.index'));

        Expense::create([
            'expense_type' => 'Utilities',
            'amount' => 1500,
            'expense_date' => $today,
            'notes' => 'Revert me',
            'created_by' => $graph['user']->id,
        ]);

        $this->assertEquals(1400, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseCount('tank_refills', 1);
        $this->assertDatabaseCount('expenses', 1);

        $this->actingAs($graph['user'])
            ->post(route('data-management.revert'), [
                'from_date' => $today,
                'confirm_revert' => '1',
                'confirm_text' => 'REVERT',
            ])
            ->assertRedirect(route('data-management.index'))
            ->assertSessionHas('success');

        $this->assertEquals(1000, (float) $graph['tank']->fresh()->current_stock_liters);
        $this->assertDatabaseCount('tank_refills', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseHas('tanks', ['id' => $graph['tank']->id]);
        $this->assertDatabaseHas('employees', ['id' => $graph['employee']->id]);
    }

    public function test_revert_requires_confirmation_text(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)
            ->from(route('data-management.index'))
            ->post(route('data-management.revert'), [
                'from_date' => now()->toDateString(),
                'confirm_revert' => '1',
                'confirm_text' => 'YES',
            ])
            ->assertSessionHasErrors('confirm_text');
    }
}
