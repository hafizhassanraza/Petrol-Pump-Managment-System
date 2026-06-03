<?php

namespace Tests\Feature\FuelStation;

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
}
