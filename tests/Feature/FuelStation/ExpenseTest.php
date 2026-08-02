<?php

namespace Tests\Feature\FuelStation;

use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_expense_can_be_recorded(): void
    {
        $user = $this->createOwner();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'expense_type' => 'Utilities',
            'amount' => 1500.50,
            'expense_date' => now()->toDateString(),
            'description' => 'Electric bill',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('expenses', [
            'expense_type' => 'Utilities',
            'amount' => 1500.50,
            'created_by' => $user->id,
        ]);
    }

    public function test_expense_can_be_updated(): void
    {
        $user = $this->createOwner();

        $expense = Expense::create([
            'expense_type' => 'Maintenance',
            'amount' => 500,
            'expense_date' => now()->toDateString(),
            'notes' => 'Pump repair',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'expense_type' => 'Repair',
            'amount' => 750,
            'expense_date' => now()->toDateString(),
            'notes' => 'Updated repair cost',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals('Repair', $expense->expense_type);
        $this->assertEquals(750, (float) $expense->amount);
        $this->assertEquals('Updated repair cost', $expense->notes);
    }

    public function test_salary_expense_recalculates_from_active_employees_on_update(): void
    {
        $user = $this->createOwner();

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

        $expense = Expense::create([
            'expense_type' => 'Salary',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $expected = (float) Employee::where('status', 1)->sum('salary');

        $response = $this->actingAs($user)->put(route('expenses.update', $expense), [
            'expense_type' => 'Salary',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
            'notes' => 'Monthly payroll',
        ]);

        $response->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertEquals(55000, $expected);
        $this->assertEquals($expected, (float) $expense->amount);
        $this->assertEquals('Monthly payroll', $expense->notes);
    }
}
