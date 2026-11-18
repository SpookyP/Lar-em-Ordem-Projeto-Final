<?php

namespace Tests\Feature\Database;

use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The seeded data has to look like real data, otherwise features that depend on it (matching homes
 * to providers) can't be tried or tested.
 */
class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_every_seeded_home_is_in_a_real_service_zone(): void
    {
        $outside = Property::with('address')->get()->reject(
            fn (Property $property) => DB::table('service_zones')
                ->where('district', $property->address->district)
                ->where('county', $property->address->county)
                ->exists()
        );

        $this->assertCount(0, $outside, 'Homes outside every service zone: ' . $outside->pluck('address.county')->implode(', '));
    }

    public function test_factory_users_never_clash_with_seeded_emails(): void
    {
        User::factory()->count(100)->create();

        $this->assertSame(User::count(), User::distinct()->count('email'));
    }
}