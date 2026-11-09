<?php

namespace Tests\Feature\Privacy;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\File\File;
use App\Models\Invoice\Consumption;
use App\Models\Invoice\Invoice;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\Resident;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use App\Models\Vault\Document;
use App\Models\Vault\DocumentCategory;
use App\Models\Vault\Notification;
use App\Services\Invoice\InvoiceService;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Right to erasure for residents (RGPD art. 17): properties, invoices, vault documents and assistance
 * requests are deleted with their files; only what a provider's job history needs stays, anonymised.
 */
class ResidentDataErasureTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;
    private Resident $resident;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('r2');

        $this->user = User::where('email', 'morador@example.com')->firstOrFail();
        $this->resident = $this->user->resident;
        $this->property = Property::findOrFail($this->resident->contracts()->value('property_id'));
    }

    private function deleteAccount(): TestResponse
    {
        // logging out at the end needs the SPA session
        return $this->actingAs($this->user)
            ->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->deleteJson('/api/user');
    }

    // a file as FileStorageService leaves it: a row in `files` and the object in R2
    private function storedFile(string $category): File
    {
        $file = File::factory()->create([
            'user_id'  => $this->user->id,
            'category' => $category,
            'path'     => "users/{$this->user->id}/{$category}/" . Str::ulid() . '.pdf',
        ]);
        Storage::disk('r2')->put($file->path, 'pdf');

        return $file;
    }

    private function request(string $status = 'open'): AssistanceRequest
    {
        return AssistanceRequest::factory()->create([
            'property_id' => $this->property->id,
            'resident_id' => $this->resident->id,
            'title'       => 'Torneira da cozinha',
            'description' => 'Ligar à Ana depois das 18h, 912 000 000.',
            'status'      => $status,
        ]);
    }

    private function withPhoto(AssistanceRequest $request): File
    {
        $photo = $this->storedFile('assistance_requests');
        $request->attachments()->create(['file_id' => $photo->id]);

        return $photo;
    }

    private function assertFileGone(File $file): void
    {
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('r2')->assertMissing($file->path);
    }

    // -------------------------------------------------------------- invoices

    public function test_invoices_their_pdfs_and_unsaved_uploads_are_deleted(): void
    {
        $pdf = $this->storedFile(InvoiceService::FILE_CATEGORY);
        $invoice = Invoice::factory()->create([
            'resident_id' => $this->resident->id,
            'property_id' => $this->property->id,
            'file_id'     => $pdf->id,
        ]);
        Consumption::factory()->create(['invoice_id' => $invoice->id, 'property_id' => $this->property->id]);
        $neverSaved = $this->storedFile(InvoiceService::FILE_CATEGORY);

        $this->deleteAccount()->assertOk();

        $this->assertSame(0, Invoice::where('resident_id', $this->resident->id)->count());
        $this->assertSame(0, Consumption::where('invoice_id', $invoice->id)->count());
        $this->assertFileGone($pdf);
        $this->assertFileGone($neverSaved);
    }

    // ------------------------------------------------------- vault documents

    public function test_vault_documents_their_files_and_alerts_are_deleted(): void
    {
        $file = $this->storedFile('vault_documents');
        $document = Document::create([
            'property_id'          => $this->property->id,
            'document_category_id' => DocumentCategory::query()->value('id'),
            'file_id'              => $file->id,
            'name'                 => 'Seguro da casa',
        ]);
        // an alert whose text doesn't mention the document by name still has to go
        Notification::create([
            'property_id' => $this->property->id,
            'type'        => 'expiry',
            'title'       => 'Documento a expirar',
            'message'     => 'Um documento da sua casa expira em breve.',
            'alert_date'  => now(),
        ]);

        $this->deleteAccount()->assertOk();

        $this->assertFalse(Document::whereKey($document->id)->exists());
        $this->assertSame(0, Notification::where('property_id', $this->property->id)->count());
        $this->assertFileGone($file);
    }

    // --------------------------------------------------- assistance requests

     public function test_a_request_without_proposals_is_deleted_with_its_photos(): void
    {
        $request = $this->request('open');
        $photo = $this->withPhoto($request);

        $this->deleteAccount()->assertOk();

        $this->assertFalse(AssistanceRequest::withTrashed()->whereKey($request->id)->exists());
        $this->assertFileGone($photo);
    }

    public function test_an_open_request_with_proposals_stays_for_the_providers_cancelled_and_anonymised(): void
    {
        $request = $this->request('open');
        $photo = $this->withPhoto($request);
        $proposal = Proposal::factory()->create([
            'assistance_request_id' => $request->id,
            'service_provider_id'   => ServiceProviderModel::query()->value('id'),
            'status'                => 'pending',
        ]);

        $this->deleteAccount()->assertOk();

        $kept = AssistanceRequest::withTrashed()->findOrFail($request->id);
        $this->assertSame('cancelled', $kept->status);
        $this->assertSame(PersonalDataEraser::REMOVED_REQUEST, $kept->title);
        $this->assertSame('', $kept->description);
        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertFileGone($photo);
    }

    public function test_a_request_with_an_accepted_proposal_stays_for_the_provider_but_anonymised(): void
    {
        $request = $this->request('closed');
        $photo = $this->withPhoto($request);
        $proposal = Proposal::factory()->create([
            'assistance_request_id' => $request->id,
            'service_provider_id'   => ServiceProviderModel::query()->value('id'),
            'status'                => 'accepted',
        ]);

        $this->deleteAccount()->assertOk();

        $kept = AssistanceRequest::withTrashed()->findOrFail($request->id);
        $this->assertSame(PersonalDataEraser::REMOVED_REQUEST, $kept->title);
        $this->assertSame('', $kept->description);
        $this->assertSame('accepted', $proposal->fresh()->status);
        $this->assertFileGone($photo);
    }

    // ------------------------------------------------------------------ property

    public function test_the_property_is_removed_and_its_address_overwritten(): void
    {
        $this->deleteAccount()->assertOk();

        $this->assertSoftDeleted('properties', ['id' => $this->property->id]);

        $address = Address::withTrashed()->findOrFail($this->property->address_id);
        $this->assertSoftDeleted('addresses', ['id' => $address->id]);
        $this->assertSame('Morada removida', $address->street);
        $this->assertSame('0000-000', $address->postal_code);
        $this->assertNull($address->door);
        $this->assertNull($address->fraction);

        $this->assertSame(0, PropertyContract::where('resident_id', $this->resident->id)->count());
    }

    // --------------------------------------------------------------- refusal

    public function test_a_refused_deletion_erases_nothing_not_even_files(): void
    {
        $pdf = $this->storedFile(InvoiceService::FILE_CATEGORY);
        Invoice::factory()->create([
            'resident_id' => $this->resident->id,
            'property_id' => $this->property->id,
            'file_id'     => $pdf->id,
        ]);

        // the same account also administers a condominium, so the erasure is refused
        $this->user->assignRole('condominium_admin');
        $admin = CondominiumAdmin::factory()->create(['user_id' => $this->user->id]);
        Condominium::forceCreate([
            'condominium_admin_id' => $admin->id,
            'address_id'           => Address::factory()->create()->id,
            'name'                 => 'Edifício Aurora',
            'invite_code'          => 'AURORA26',
        ]);

        $this->deleteAccount()->assertStatus(422);

        $this->assertDatabaseHas('files', ['id' => $pdf->id]);
        Storage::disk('r2')->assertExists($pdf->path);
        $this->assertFalse($this->property->fresh()->trashed());
    }
}