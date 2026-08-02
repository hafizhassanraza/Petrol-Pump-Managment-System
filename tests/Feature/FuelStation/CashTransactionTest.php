<?php

namespace Tests\Feature\FuelStation;

use App\Models\CashTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class CashTransactionTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_cash_transactions(): void
    {
        $this->get(route('cash-transactions.index'))->assertRedirect(route('login'));
        $this->get(route('cash-transactions.create'))->assertRedirect(route('login'));
    }

    public function test_can_record_cash_in(): void
    {
        $user = $this->createOwner();

        $response = $this->actingAs($user)->post(route('cash-transactions.store'), [
            'type' => 'cash_in',
            'category' => 'Owner Investment',
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'reference_no' => 'CI-001',
            'notes' => 'Till top-up',
        ]);

        $response->assertRedirect(route('cash-transactions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'cash_in',
            'category' => 'Owner Investment',
            'amount' => 50000,
            'created_by' => $user->id,
        ]);
    }

    public function test_can_record_and_update_cash_out(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)->post(route('cash-transactions.store'), [
            'type' => 'cash_out',
            'category' => 'Bank Deposit',
            'amount' => 20000,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertRedirect();

        $txn = CashTransaction::first();

        $this->actingAs($user)->put(route('cash-transactions.update', $txn), [
            'type' => 'cash_out',
            'category' => 'Owner Draw',
            'amount' => 15000,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'online',
            'notes' => 'Updated',
        ])->assertRedirect(route('cash-transactions.index'));

        $txn->refresh();
        $this->assertEquals('Owner Draw', $txn->category);
        $this->assertEquals(15000, (float) $txn->amount);
        $this->assertEquals('online', $txn->payment_method);
    }

    public function test_index_shows_period_totals(): void
    {
        $user = $this->createOwner();
        $this->travelToBusinessHours();

        $businessDate = \App\Services\BusinessDayService::currentBusinessDate()->toDateString();

        CashTransaction::create([
            'type' => 'cash_in',
            'category' => 'Other Income',
            'amount' => 10000,
            'transaction_date' => $businessDate,
            'payment_method' => 'cash',
            'created_by' => $user->id,
        ]);

        CashTransaction::create([
            'type' => 'cash_out',
            'category' => 'Bank Deposit',
            'amount' => 3000,
            'transaction_date' => $businessDate,
            'payment_method' => 'cash',
            'created_by' => $user->id,
        ]);

        $this->assertEquals(2, CashTransaction::count());
        $this->assertEquals(
            2,
            CashTransaction::whereDate('transaction_date', $businessDate)->count()
        );

        $this->actingAs($user)
            ->get(route('cash-transactions.index', [
                'filter' => 'custom',
                'from' => $businessDate,
                'to' => $businessDate,
            ]))
            ->assertOk()
            ->assertSee('Other Income')
            ->assertSee('Bank Deposit')
            ->assertSee('10,000')
            ->assertSee('3,000')
            ->assertSee('7,000');
    }
}
