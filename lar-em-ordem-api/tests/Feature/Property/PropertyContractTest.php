<?php

namespace Tests\Feature\Property;

use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Residents don't share homes: a home has at most one active contract (enforced by the model),
 * and only the resident who lives there can end it.
 */
class PropertyContractTest extends TestCase
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

    public function test_the_seeded_data_has_no_home_with_more_than_one_active_contract(): void
    {
        $shared = PropertyContract::where('is_active', true)
            ->select('property_id')
            ->groupBy('property_id')
            ->having(DB::raw('count(*)'), '>', 1)
            ->pluck('property_id');

        $this->assertEmpty($shared, 'Homes with more than one active contract: ' . $shared->implode(', '));
    }

    public function test_a_home_cannot_get_a_second_active_contract(): void
    {
        $property = $this->homeOf($this->user('morador@example.com'));

        $this->expectException(ValidationException::class);

        PropertyContract::factory()->create([
            'resident_id' => $this->user('residente@example.com')->resident->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }

    public function test_an_ended_contract_does_not_block_a_new_one(): void
    {
        $owner = $this->user('morador@example.com');
        $property = $this->homeOf($owner);
        $property->contracts()->where('is_active', true)->update(['is_active' => false]);

        PropertyContract::factory()->create([
            'resident_id' => $owner->resident->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);

        $this->assertSame(1, $property->contracts()->where('is_active', true)->count());
    }

    public function test_a_resident_can_leave_a_home(): void
    {
        $user = $this->user('morador@example.com');
        $property = $this->homeOf($user);

        $this->actingAs($user)
            ->deleteJson("/api/v1/properties/{$property->id}/terminate")
            ->assertOk()
            ->assertJsonPath('message', 'Contrato terminado.');

        $this->assertSame(0, $property->contracts()->where('resident_id', $user->resident->id)->count());

        $listed = collect($this->actingAs($user)->getJson('/api/v1/properties')->assertOk()->json('data'))->pluck('id');
        $this->assertNotContains($property->id, $listed);
    }

    public function test_someone_else_cannot_end_a_contract(): void
    {
        $property = $this->homeOf($this->user('morador@example.com'));

        $this->actingAs($this->user('residente@example.com'))
            ->deleteJson("/api/v1/properties/{$property->id}/terminate")
            ->assertForbidden();

        $this->assertTrue($property->contracts()->where('is_active', true)->exists());
    }

    public function test_a_contract_cannot_be_ended_twice(): void
    {
        $user = $this->user('morador@example.com');
        $property = $this->homeOf($user);

        $this->actingAs($user)->deleteJson("/api/v1/properties/{$property->id}/terminate")->assertOk();

        // with no active contract left the policy no longer sees the user as the owner
        $this->actingAs($user)
            ->deleteJson("/api/v1/properties/{$property->id}/terminate")
            ->assertForbidden();
    }
}