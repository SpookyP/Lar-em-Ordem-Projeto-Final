<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\Resident;
use App\Models\Vault\DocumentCategory;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\ResidentType;
use App\Services\Vault\PdfExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Illuminate\Support\Str;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUserAndProperty(): array
    {
        $this->seed([
            \Database\Seeders\PropertyTypeSeeder::class,
            \Database\Seeders\PropertyTypologySeeder::class,
            \Database\Seeders\ResidentTypeSeeder::class,
        ]);

        $propertyTypeId = PropertyType::first()->id;
        $propertyTypologyId = PropertyTypology::first()->id;
        $residentTypeId = ResidentType::first()->id;

        $user = User::factory()->create();
        $resident = Resident::factory()->create(['user_id' => $user->id]);

        $property = Property::factory()->create([
            'property_type_id' => $propertyTypeId,
            'property_typology_id' => $propertyTypologyId,
        ]);

        PropertyContract::factory()->create([
            'resident_id' => $resident->id,
            'property_id' => $property->id,
            'resident_type_id' => $residentTypeId,
            'is_active' => true,
        ]);

        return [$user, $property];
    }

    public function test_authorized_user_can_upload_pdf_and_extract_data(): void
    {
        Storage::fake('r2');

        $this->mock(PdfExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractInfo')
                ->once()
                ->andReturn([
                    'issue_date' => '2023-01-01',
                    'expiration_date' => '2030-12-31',
                    'extracted_text' => 'Mocked text from PDF'
                ]);
        });

        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $file = UploadedFile::fake()->create('energy_certificate.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/properties/{$property->id}/documents", [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'description' => 'Test document payload',
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'property_id',
                    'document_category_id',
                    'file_id',
                ]
            ]);

        $this->assertDatabaseHas('vault_documents', [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
        ]);
    }

    public function test_upload_fails_if_file_is_not_pdf(): void
    {
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/properties/{$property->id}/documents", [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_authorized_user_can_list_property_documents(): void
    {
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => 'fatura_luz.pdf',
            'description' => 'Fatura de teste',
            'file_id' => (string) Str::uuid(),
            'expiration_date' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/properties/{$property->id}/documents");

        $response->assertStatus(200);
    }

    public function test_authorized_user_can_download_document(): void
    {
        Storage::fake('local');
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $fileId = (string) Str::uuid();
        $fileName = 'contrato_arrendamento.pdf';

        Storage::disk('local')->put("vault_documents/{$fileId}", 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => $fileName,
            'description' => 'Contrato',
            'file_id' => $fileId,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/documents/{$document->id}/download");

        $response->assertStatus(200)
            ->assertDownload($fileName);
    }

    public function test_authorized_user_can_delete_document(): void
    {
        Storage::fake('local');
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $fileName = 'planta_casa.pdf';
        Storage::disk('local')->put("vault_documents/{$fileName}", 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => $fileName,
            'description' => 'Planta',
            'file_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vault_documents', ['id' => $document->id]);
    }

    public function test_unauthorized_user_cannot_access_documents_of_another_property(): void
    {
        [$owner, $property] = $this->createAuthorizedUserAndProperty();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/properties/{$property->id}/documents");

        $response->assertStatus(403);
    }
}
