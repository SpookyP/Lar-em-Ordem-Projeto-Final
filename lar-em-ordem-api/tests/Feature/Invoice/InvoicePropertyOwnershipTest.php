<?php

namespace Tests\Feature\Invoice;

use App\Models\Invoice\ConsumptionType;
use App\Models\Property\Property;
use App\Models\User\Resident;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesPropertyData;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * An invoice can only be registered on a property where the signed-in resident has an active contract.
 */
class InvoicePropertyOwnershipTest extends TestCase
{
    use RefreshDatabase;
    use CreatesPropertyData;

    protected $seed = true;

    private User $user;
    private ConsumptionType $type;
    private int $invoicesBefore;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->type = ConsumptionType::firstOrCreate(['name' => 'Eletricidade'], ['unit_of_measure' => 'kWh']);

        $this->invoicesBefore = DB::table('invoices')->count();
    }

    private function payload(Property $property, array $overrides = []): array
    {
        return array_merge([
            'property_id'    => $property->id,
            'invoice_number' => 'FT-0001',
            'issue_date'     => '2025-04-10',
            'period_start'   => '2025-03-01',
            'period_end'     => '2025-03-31',
            'total_amount'   => 57.20,
            'supplier'       => 'EDP',
            'consumptions'   => [
                ['consumption_type_id' => $this->type->id, 'amount' => 150, 'cost' => 22.5],
            ],
        ], $overrides);
    }

    public function test_invoice_can_be_registered_on_an_own_property(): void
    {
        $property = $this->makeProperty();
        $this->giveContract($this->user, $property);

        $this->postJson('/api/v1/invoices', $this->payload($property))->assertCreated();

        $residentId = Resident::where('user_id', $this->user->id)->value('id');
        $this->assertDatabaseHas('invoices', ['resident_id' => $residentId, 'property_id' => $property->id]);
    }

    public function test_invoice_cannot_be_registered_on_someone_elses_property(): void
    {
        $owner = User::factory()->create();
        $property = $this->makeProperty();
        $this->giveContract($owner, $property);

        // the signed-in user is a resident too, but of another property
        $this->giveContract($this->user, $this->makeProperty());

        $this->postJson('/api/v1/invoices', $this->payload($property))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);

        $this->assertDatabaseMissing('invoices', ['property_id' => $property->id]);
        $this->assertDatabaseMissing('consumptions', ['property_id' => $property->id]);
    }

    public function test_invoice_cannot_be_registered_on_a_property_with_an_ended_contract(): void
    {
        $property = $this->makeProperty();
        $this->giveContract($this->user, $property, active: false);

        $this->postJson('/api/v1/invoices', $this->payload($property))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);

        $this->assertSame($this->invoicesBefore, DB::table('invoices')->count());
    }

    public function test_user_without_a_resident_profile_cannot_register_invoices(): void
    {
        $property = $this->makeProperty();
        $this->giveContract(User::factory()->create(), $property);

        // $this->user has no resident profile at all
        $this->postJson('/api/v1/invoices', $this->payload($property))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);

        $this->assertSame($this->invoicesBefore, DB::table('invoices')->count());
    }
}