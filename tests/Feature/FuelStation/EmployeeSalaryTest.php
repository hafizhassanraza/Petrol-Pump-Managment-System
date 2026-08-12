<?php

namespace Tests\Feature\FuelStation;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Services\BusinessDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class EmployeeSalaryTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_employee_salaries(): void
    {
        $this->get(route('employee-salaries.index'))->assertRedirect(route('login'));
        $this->get(route('reports.employee-salaries'))->assertRedirect(route('login'));
    }

    public function test_can_record_full_advance_and_partial_salary(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();
        $month = BusinessDayService::currentBusinessDate()->format('Y-m');

        $employee = Employee::create([
            'employee_code' => 'EMP-PAY-01',
            'name' => 'Salary Worker',
            'status' => true,
            'salary' => 30000,
            'joining_date' => now()->subMonth(),
        ]);

        $this->actingAs($user)->post(route('employee-salaries.store'), [
            'employee_id' => $employee->id,
            'type' => 'advance',
            'amount' => 5000,
            'payment_date' => $date,
            'salary_month' => $month,
            'payment_method' => 'cash',
            'notes' => 'Mid month advance',
        ])->assertRedirect(route('employee-salaries.index'));

        $this->actingAs($user)->post(route('employee-salaries.store'), [
            'employee_id' => $employee->id,
            'type' => 'partial',
            'amount' => 10000,
            'payment_date' => $date,
            'salary_month' => $month,
            'payment_method' => 'bank',
        ])->assertRedirect(route('employee-salaries.index'));

        $this->actingAs($user)->post(route('employee-salaries.store'), [
            'employee_id' => $employee->id,
            'type' => 'full',
            'amount' => 30000,
            'payment_date' => $date,
            'salary_month' => $month,
            'payment_method' => 'cash',
        ])->assertRedirect(route('employee-salaries.index'));

        $this->assertDatabaseCount('employee_salaries', 3);
        $this->assertEquals(45000.0, (float) EmployeeSalary::sum('amount'));

        $this->actingAs($user)
            ->get(route('employee-salaries.index', ['filter' => 'custom', 'from' => $date, 'to' => $date]))
            ->assertOk()
            ->assertSee('Advance')
            ->assertSee('Partial Salary')
            ->assertSee('Full Salary');
    }

    public function test_salary_is_separate_from_expenses_on_dashboard_and_profit_loss(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();
        $month = BusinessDayService::currentBusinessDate()->format('Y-m');

        $employee = Employee::create([
            'employee_code' => 'EMP-PAY-02',
            'name' => 'Payroll Staff',
            'status' => true,
            'salary' => 20000,
            'joining_date' => now()->subMonth(),
        ]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'expense_type' => 'Maintenance',
            'amount' => 1000,
            'expense_date' => $date,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('employee-salaries.store'), [
            'employee_id' => $employee->id,
            'type' => 'full',
            'amount' => 20000,
            'payment_date' => $date,
            'salary_month' => $month,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseMissing('expenses', ['expense_type' => 'Salary']);
        $this->assertEquals(1000.0, (float) Expense::operating()->sum('amount'));
        $this->assertEquals(20000.0, (float) EmployeeSalary::sum('amount'));

        $this->actingAs($user)
            ->get(route('dashboard', ['filter' => 'custom', 'from' => $date, 'to' => $date]))
            ->assertOk()
            ->assertSee('Employee Salaries', false)
            ->assertSee('20000', false);

        $this->actingAs($user)
            ->get(route('reports.profit-loss', ['filter' => 'custom', 'from' => $date, 'to' => $date]))
            ->assertOk()
            ->assertSee('Employee Salaries', false)
            ->assertSee('Operating Expenses', false);

        $this->actingAs($user)
            ->get(route('reports.employee-salaries', ['filter' => 'custom', 'from' => $date, 'to' => $date]))
            ->assertOk()
            ->assertSee('Payroll Staff')
            ->assertSee('Download PDF');

        $this->actingAs($user)
            ->get(route('reports.employee-salaries.pdf', ['filter' => 'custom', 'from' => $date, 'to' => $date]))
            ->assertOk();
    }

    public function test_expenses_no_longer_accept_salary_type(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();

        $this->actingAs($user)->from(route('expenses.create'))->post(route('expenses.store'), [
            'expense_type' => 'Salary',
            'amount' => 1000,
            'expense_date' => $date,
        ])->assertSessionHasErrors('expense_type');

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertDontSee('>Salary</option>', false);
    }
}
