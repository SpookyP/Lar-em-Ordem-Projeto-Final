<?php

namespace Tests\Feature\Property;

use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A home's address is always its own: created with the home, edited in place, never swapped
 * for an existing address (which could be someone else's home or a condominium building).
 */
class PropertyAddressTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function homeOf(User $user): Property
    {
        return Property::findOrFail($user->resident->contracts()->where('is_active', true)->value('property_id'));
    }

    public function test_an_existing_address_cannot_be_chosen_when_editing(): void
    {
        $owner = $this->user('morador@example.com');
        $property = $this->homeOf($owner);
        $mine = $property->address_id;
        $theirs = $this->homeOf($this->user('residente@example.com'))->address_id;

        $this->actingAs($owner)
            ->putJson("/api/v1/properties/{$property->id}", [
                'address_id' => $theirs,
                'address' => ['street' => 'Rua Nova', 'postal_code' => '4000-001'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);

        $this->assertSame($mine, $property->fresh()->address_id);
        $this->assertNotSame('Rua Nova', Address::find($theirs)->street);
    }

    public function test_editing_the_address_changes_the_homes_own_address(): void
    {
        $owner = $this->user('morador@example.com');
        $property = $this->homeOf($owner);
        $mine = $property->address_id;

        $this->actingAs($owner)
            ->putJson("/api/v1/properties/{$property->id}", [
                'address' => ['street' => 'Rua Nova', 'postal_code' => '4000-001'],
            ])
            ->assertOk();

        $this->assertSame($mine, $property->fresh()->address_id);
        $this->assertSame('Rua Nova', Address::find($mine)->street);
    }

    public function test_an_existing_address_cannot_be_chosen_when_creating(): void
    {
        $theirs = $this->homeOf($this->user('residente@example.com'))->address_id;

        $this->actingAs($this->user('morador@example.com'))
            ->postJson('/api/v1/properties', ['address_id' => $theirs])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id', 'address']);
    }
}