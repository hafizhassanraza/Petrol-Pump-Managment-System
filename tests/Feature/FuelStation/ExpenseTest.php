<?php

namespace Tests\Feature\FuelStation;

use App\Models\Employee;
use App\Models\Expense;
use App\Services\BusinessDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_expense_can_be_recorded(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'expense_type' => 'Electricity Bill',
            'amount' => 1500.50,
            'expense_date' => $date,
            'notes' => 'Electric bill',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('expenses', [
            'expense_type' => 'Electricity Bill',
            'amount' => 1500.50,
            'created_by' => $user->id,
        ]);
        $this->assertEquals($date, Expense::first()->expense_date->toDateString());
    }

    public function test_expense_create_defaults_date_and_keeps_entered_amount(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $defaultDate = BusinessDayService::currentBusinessDate()->toDateString();

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('name="expense_date"', false)
            ->assertSee($defaultDate);

        $this->actingAs($user)->post(route('expenses.store'), [
            'expense_type' => 'Maintenance',
            'amount' => 3200,
            'expense_date' => $defaultDate,
            'notes' => 'Pump service',
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::first();
        $this->assertNotNull($expense);
        $this->assertEquals(3200.0, (float) $expense->amount);
        $this->assertEquals($defaultDate, $expense->expense_date->toDateString());
    }

    public function test_expense_can_be_updated(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();

        $expense = Expense::create([
            'expense_type' => 'Maintenance',
            'amount' => 500,
            'expense_date' => $date,
            'notes' => 'Pump repair',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'expense_type' => 'Repair',
            'amount' => 750,
            'expense_date' => $date,
            'notes' => 'Updated repair cost',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals('Repair', $expense->expense_type);
        $this->assertEquals(750, (float) $expense->amount);
        $this->assertEquals('Updated repair cost', $expense->notes);
        $this->assertEquals($date, $expense->expense_date->toDateString());
    }

    public function test_salary_expense_uses_active_employee_salary_total(): void
    {
        $this->travelToBusinessHours();
        $user = $this->createOwner();
        $date = BusinessDayService::currentBusinessDate()->toDateString();

        Employee::create([
            'employee_code' => 'EMP-SAL-01',
            'name' => 'First Attendant',
            'status' => true,
            'salary' => 30000,
            'joining_date' => now()->subMonth(),
        ]);

        Employee::create([
            'employee_code' => 'EMP-SAL-02',
            'name' => 'Second Attendant',
            'status' => true,
            'salary' => 25000,
            'joining_date' => now()->subMonth(),
        ]);

        Employee::create([
            'employee_code' => 'EMP-SAL-03',
            'name' => 'Inactive Attendant',
            'status' => false,
            'salary' => 40000,
            'joining_date' => now()->subMonth(),
        ]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'expense_type' => 'Salary',
            'amount' => 1,
            'expense_date' => $date,
            'notes' => 'Monthly payroll',
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::first();
        $this->assertEquals(55000.0, (float) $expense->amount);
        $this->assertEquals($date, $expense->expense_date->toDateString());

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'expense_type' => 'Salary',
            'amount' => 1000,
            'expense_date' => $date,
            'notes' => 'Monthly payroll updated',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $expense->refresh();
        $this->assertEquals(55000.0, (float) $expense->amount);
        $this->assertEquals('Monthly payroll updated', $expense->notes);
    }
}
