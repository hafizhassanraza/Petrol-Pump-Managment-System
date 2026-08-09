<?php

namespace Tests\Feature\FuelStation;

use App\Models\Dispenser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFuelStationFixtures;
use Tests\TestCase;

class DispenserTest extends TestCase
{
    use CreatesFuelStationFixtures;
    use RefreshDatabase;

    public function test_guest_cannot_access_dispensers(): void
    {
        $this->get(route('dispensers.index'))->assertRedirect(route('login'));
        $this->get(route('dispensers.create'))->assertRedirect(route('login'));
    }

    public function test_can_list_and_create_dispenser(): void
    {
        $user = $this->createOwner();

        $this->actingAs($user)->get(route('dispensers.index'))->assertOk();
        $this->actingAs($user)->get(route('dispensers.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('dispensers.store'), [
            'dispenser_code' => 'D-NEW-01',
            'company' => 'Wayne',
            'model' => 'X1',
            'status' => 1,
        ]);

        $response->assertRedirect(route('dispensers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('dispensers', [
            'dispenser_code' => 'D-NEW-01',
            'company' => 'Wayne',
            'model' => 'X1',
        ]);
    }

    public function test_dispenser_requires_unique_code(): void
    {
        $graph = $this->createFuelStationGraph();

        $response = $this->actingAs($graph['user'])->from(route('dispensers.create'))->post(route('dispensers.store'), [
            'dispenser_code' => $graph['dispenser']->dispenser_code,
            'company' => 'Dup',
            'model' => 'Dup',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('dispenser_code');
    }

    public function test_can_update_and_delete_dispenser(): void
    {
        $graph = $this->createFuelStationGraph();
        $dispenser = $graph['dispenser'];

        $this->actingAs($graph['user'])->get(route('dispensers.edit', $dispenser))->assertOk();

        $response = $this->actingAs($graph['user'])->put(route('dispensers.update', $dispenser), [
            'dispenser_code' => 'D-UPDATED',
            'company' => 'UpdatedCo',
            'model' => 'M2',
            'status' => 1,
        ]);

        $response->assertRedirect(route('dispensers.index'));
        $this->assertDatabaseHas('dispensers', [
            'id' => $dispenser->id,
            'dispenser_code' => 'D-UPDATED',
            'company' => 'UpdatedCo',
        ]);

        $orphan = Dispenser::create([
            'dispenser_code' => 'D-DEL',
            'company' => 'Temp',
            'model' => 'Temp',
            'status' => true,
        ]);

        $this->actingAs($graph['user'])
            ->delete(route('dispensers.destroy', $orphan))
            ->assertRedirect(route('dispensers.index'));

        $this->assertDatabaseMissing('dispensers', ['id' => $orphan->id]);
    }
}
