<?php

namespace Tests\Feature\Database;

use App\Models\Invoice\Invoice;
use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\User;
use App\Models\Vault\Document;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ResidenteConsumptionSeeder;
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
        $this->seed(DemoSeeder::class);
        $outside = Property::with('address')->get()->reject(
            fn (Property $property) => DB::table('service_zones')
                ->where('district', $property->address->district)
                ->where('county', $property->address->county)
                ->exists()
        );

        $this->assertCount(0, $outside, 'Homes outside every service zone: ' . $outside->pluck('address.county')->implode(', '));
    }

    public function test_the_presentation_data_follows_the_rules_of_the_app(): void
    {
        // the DatabaseSeeder only adds these outside the tests
        $this->seed(DemoSeeder::class);
        (new ResidenteConsumptionSeeder)->setContainer($this->app)->__invoke(['email' => 'morador@example.com', 'allHomes' => true]);

        // a closed request has exactly one accepted proposal; open and cancelled ones have none
        AssistanceRequest::with('proposals')->get()->each(fn (AssistanceRequest $request) => $this->assertSame(
            $request->status === 'closed' ? 1 : 0,
            $request->proposals->where('status', 'accepted')->count(),
            $request->title
        ));

        $this->assertSame(0, Proposal::whereColumn('validity', '>', 'available_date')->count(), 'A proposal is valid past its available date.');

        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        $this->assertFalse(Document::whereHas('property.contracts', fn ($contract) => $contract
            ->where('is_active', true)
            ->whereHas('residentType', fn ($type) => $type->where('type', 'Tenant'))
        )->exists(), 'A rented home has documents in the vault.');
    }

    public function test_factory_users_never_clash_with_seeded_emails(): void
    {
        User::factory()->count(100)->create();

        $this->assertSame(User::count(), User::distinct()->count('email'));
    }
}