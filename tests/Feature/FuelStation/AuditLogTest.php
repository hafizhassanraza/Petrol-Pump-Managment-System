<?php

namespace Tests\Feature\FuelStation;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_audit_logs(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
        $this->get(route('audit-logs.pdf'))->assertRedirect(route('login'));
    }

    public function test_owner_can_view_audit_logs_index(): void
    {
        $user = $this->createOwner();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'module' => 'tanks',
            'description' => 'Created Tank #1',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('All Activities')
            ->assertSee('Created Tank #1')
            ->assertSee('Download PDF');
    }

    public function test_mutating_actions_are_recorded_in_audit_log(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)->post(route('dispensers.store'), [
            'dispenser_code' => 'D-AUDIT-01',
            'company' => 'AuditCo',
            'model' => 'A1',
            'status' => 1,
        ])->assertRedirect(route('dispensers.index'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'created',
            'module' => 'dispensers',
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('module', 'dispensers')
                ->where('description', 'like', '%D-AUDIT-01%')
                ->exists()
        );
    }

    public function test_dashboard_view_and_report_export_are_recorded(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'viewed',
            'module' => 'dashboard',
        ]);

        $this->actingAs($user)
            ->get(route('reports.profit-loss.pdf', ['filter' => 'today']))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'exported',
            'module' => 'reports',
        ]);
    }

    public function test_login_and_logout_are_recorded(): void
    {
        $user = $this->createOwner();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logged_in',
            'module' => 'auth',
        ]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logged_out',
            'module' => 'auth',
        ]);
    }

    public function test_audit_logs_pdf_downloads(): void
    {
        $user = $this->createOwner();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'module' => 'employees',
            'description' => 'Updated Employee #2',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('audit-logs.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_audit_logs_can_filter_by_action(): void
    {
        $user = $this->createOwner();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'module' => 'tanks',
            'description' => 'Created Tank Unique A',
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'deleted',
            'module' => 'tanks',
            'description' => 'Deleted Tank Unique B',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('audit-logs.index', ['action' => 'deleted', 'filter' => 'today']))
            ->assertOk()
            ->assertSee('Deleted Tank Unique B')
            ->assertDontSee('Created Tank Unique A');
    }

    public function test_audit_log_seeder_creates_demo_entries(): void
    {
        $this->createOwner();

        $this->seed(\Database\Seeders\AuditLogSeeder::class);

        $this->assertGreaterThanOrEqual(20, AuditLog::count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logged_in',
            'module' => 'auth',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'viewed',
            'module' => 'dashboard',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'exported',
            'module' => 'reports',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'module' => 'employee-salaries',
        ]);
    }
}
