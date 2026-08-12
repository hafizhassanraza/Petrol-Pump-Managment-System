<?php

namespace Tests\Feature\FuelStation;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_employees(): void
    {
        $this->get(route('employees.index'))->assertRedirect(route('login'));
        $this->get(route('employees.create'))->assertRedirect(route('login'));
    }

    public function test_can_list_and_create_employee(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)->get(route('employees.index'))->assertOk();
        $this->actingAs($user)->get(route('employees.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('employees.store'), [
            'employee_code' => 'EMP-NEW-01',
            'name' => 'Ali Khan',
            'cnic' => '12345-1234567-1',
            'phone' => '03001234567',
            'salary' => 25000,
            'joining_date' => now()->toDateString(),
            'status' => 1,
        ]);

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-NEW-01',
            'name' => 'Ali Khan',
            'salary' => 25000,
        ]);
    }

    public function test_employee_requires_unique_code_and_name(): void
    {
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($graph['user'])->from(route('employees.create'))->post(route('employees.store'), [
            'employee_code' => $graph['employee']->employee_code,
            'name' => '',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors(['employee_code', 'name']);
    }

    public function test_can_update_and_delete_employee(): void
    {
        $graph = $this->createFuelStationGraph();
        $employee = $graph['employee'];

        $this->actingAs($graph['user'])->get(route('employees.edit', $employee))->assertOk();

        $response = $this->actingAs($graph['user'])->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-UPDATED',
            'name' => 'Updated Attendant',
            'cnic' => null,
            'phone' => '03009999999',
            'salary' => 32000,
            'joining_date' => now()->subMonth()->toDateString(),
            'status' => 1,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employee_code' => 'EMP-UPDATED',
            'name' => 'Updated Attendant',
            'salary' => 32000,
        ]);

        $orphan = Employee::create([
            'employee_code' => 'EMP-DEL',
            'name' => 'Temp',
            'status' => true,
            'salary' => 0,
            'joining_date' => now(),
        ]);

        $this->actingAs($graph['user'])
            ->delete(route('employees.destroy', $orphan))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $orphan->id]);
    }

    public function test_employee_payment_ledger_and_pdf(): void
    {
        $graph = $this->createFuelStationGraph();
        $employee = $graph['employee'];
        $date = now()->toDateString();

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'type' => EmployeeSalary::TYPE_ADVANCE,
            'amount' => 5000,
            'payment_date' => $date,
            'salary_month' => now()->startOfMonth()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Advance cash',
            'created_by' => $graph['user']->id,
        ]);

        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'type' => EmployeeSalary::TYPE_PARTIAL,
            'amount' => 15000,
            'payment_date' => $date,
            'salary_month' => now()->startOfMonth()->toDateString(),
            'payment_method' => 'bank',
            'notes' => 'Partial balance',
            'created_by' => $graph['user']->id,
        ]);

        $this->get(route('employees.ledger', $employee))->assertRedirect(route('login'));

        $this->actingAs($graph['user'])
            ->get(route('employees.ledger', [
                'employee' => $employee,
                'filter' => 'custom',
                'from' => $date,
                'to' => $date,
            ]))
            ->assertOk()
            ->assertSee('Payment Ledger')
            ->assertSee('Advance')
            ->assertSee('Partial Salary')
            ->assertSee('20,000')
            ->assertSee(route('employees.ledger.pdf', $employee), false);

        $pdf = $this->actingAs($graph['user'])
            ->get(route('employees.ledger.pdf', [
                'employee' => $employee,
                'filter' => 'custom',
                'from' => $date,
                'to' => $date,
            ]));

        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $this->actingAs($graph['user'])
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee(route('employees.ledger', $employee), false);
    }
}
