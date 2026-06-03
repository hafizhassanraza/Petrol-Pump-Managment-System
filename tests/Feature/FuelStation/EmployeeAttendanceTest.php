<?php

namespace Tests\Feature\FuelStation;

use App\Models\EmployeeAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class EmployeeAttendanceTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_attendance(): void
    {
        $this->get(route('employee-attendances.index'))->assertRedirect(route('login'));
    }

    public function test_owner_can_mark_attendance(): void
    {
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($graph['user'])->post(route('employee-attendances.store'), [
            'employee_id' => $graph['employee']->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'check_in' => '09:00',
            'check_out' => '18:00',
        ]);

        $response->assertRedirect(route('employee-attendances.index'));
        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $graph['employee']->id,
            'status' => 'present',
        ]);
    }

    public function test_duplicate_attendance_per_day_is_rejected(): void
    {
        $graph = $this->createFuelStationGraph();
        $date = now()->toDateString();

        EmployeeAttendance::create([
            'employee_id' => $graph['employee']->id,
            'attendance_date' => $date,
            'status' => 'present',
            'recorded_by' => $graph['user']->id,
        ]);

        $response = $this->actingAs($graph['user'])->post(route('employee-attendances.store'), [
            'employee_id' => $graph['employee']->id,
            'attendance_date' => $date,
            'status' => 'absent',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, EmployeeAttendance::count());
    }

    public function test_attendance_report_page_loads(): void
    {
        $graph = $this->createFuelStationGraph();

        $this->actingAs($graph['user'])
            ->get(route('reports.attendance'))
            ->assertOk();
    }
}
