<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Vault\DocumentCategory;
use App\Models\Property\Property;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\Property\Address;
use App\Services\Vault\PdfExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Illuminate\Support\Str;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria uma Habitação utilizando os Models do Módulo 2 (Eloquent).
     * Garante que o ciclo de vida do Laravel (ex: geração de UUIDs ou IDs automáticos)
     * é respeitado, evitando falhas de integridade no SQLite.
     */
    private function createTestProperty(): int
    {
        $type = new PropertyType();
        $type->type = 'Apartamento';
        $type->save();

        $typology = new PropertyTypology();
        $typology->typology = 'T2';
        $typology->save();

        $address = new Address();
        $address->street = 'Rua Teste';
        $address->postal_code = '4000-000';
        $address->door = '1A';
        $address->county = 'Porto';
        $address->location = 'Porto';
        $address->district = 'Porto';
        $address->save();

        $property = new Property();
        $property->property_type_id = $type->id;
        $property->property_typology_id = $typology->id;
        $property->address_id = $address->id;
        $property->area = 100;
        $property->fraction = 'A';
        $property->save();

        return $property->id;
    }

    public function test_user_can_upload_pdf_and_extract_data(): void
    {
        Storage::fake('local');

        $this->mock(PdfExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractInfo')
                ->once()
                ->andReturn([
                    'issue_date' => '2023-01-01',
                    'expiration_date' => '2030-12-31',
                    'extracted_text' => 'Mocked text from PDF'
                ]);
        });

        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();

        $propertyId = $this->createTestProperty();

        $file = UploadedFile::fake()->create('energy_certificate.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/documents', [
            'property_id' => $propertyId,
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
                    'expiration_date'
                ]
            ]);

        $this->assertDatabaseHas('vault_documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => 'energy_certificate.pdf',
            'expiration_date' => '2030-12-31 00:00:00',
        ]);
    }

    public function test_upload_fails_if_file_is_not_pdf(): void
    {
        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();
        $propertyId = $this->createTestProperty();

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_list_property_documents(): void
    {
        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();
        $propertyId = $this->createTestProperty();

        \App\Models\Vault\Document::create([
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => 'fatura_luz.pdf',
            'description' => 'Fatura de teste',
            'file_id' => (string) Str::uuid(),
            'expiration_date' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/documents?property_id={$propertyId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'property_id', 'document_category_id', 'file_id']
                ]
            ]);
    }

    public function test_user_can_download_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();
        $propertyId = $this->createTestProperty();

        // Geramos o UUID primeiro
        $fileId = (string) \Illuminate\Support\Str::uuid();
        $fileName = 'contrato_arrendamento.pdf';

        // Criamos o ficheiro falso no disco usando o UUID
        Storage::disk('local')->put("vault_documents/{$fileId}", 'conteudo falso do pdf');

        // Criamos o registo na BD a apontar para o mesmo UUID
        $document = \App\Models\Vault\Document::create([
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => $fileName,
            'description' => 'Contrato',
            'file_id' => $fileId,
        ]);

        // Testamos a rota
        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/documents/{$document->id}/download");

        // O Controller vai encontrar o UUID no disco e devolver 200
        $response->assertStatus(200)
            ->assertDownload($fileName);
    }

    public function test_user_can_delete_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();
        $propertyId = $this->createTestProperty();

        $fileName = 'planta_casa.pdf';
        Storage::disk('local')->put("vault_documents/{$fileName}", 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $propertyId,
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
}
