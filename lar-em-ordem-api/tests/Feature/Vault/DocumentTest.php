<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Vault\DocumentCategory;
use App\Services\Vault\PdfExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria uma Habitação de forma dinâmica para os testes do Cofre.
     * Isola o Módulo 3 dos problemas dos Seeders globais da equipa
     * e resolve o problema dos "IDs hardcoded" reportado pelo Rafa,
     * utilizando os nomes corretos das colunas em inglês.
     */
    private function createTestProperty(): int
    {
        Schema::disableForeignKeyConstraints();

        // Inserção com base na migration property_types
        $typeId = DB::table('property_types')->insertGetId([
            'type' => 'Apartamento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assumindo a tradução padrão para property_typologies
        $typologyId = DB::table('property_typologies')->insertGetId([
            'typology' => 'T2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Inserção com base na migration addresses
        $addressId = DB::table('addresses')->insertGetId([
            'street' => 'Rua Teste',
            'postal_code' => '4000-000',
            'county' => 'Porto',
            'location' => 'Porto',
            'district' => 'Porto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => $typeId,
            'property_typology_id' => $typologyId,
            'address_id' => $addressId,
            'area' => 100,
            'fraction' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::enableForeignKeyConstraints();

        return $propertyId;
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
                    'file_path',
                    'expiration_date'
                ]
            ]);

        $this->assertDatabaseHas('vault_documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => 'energy_certificate.pdf',
            'expiration_date' => '2030-12-31 00:00:00',
        ]);

        Storage::disk('local')->assertExists('vault_documents/' . $file->hashName());
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
            'file_path' => 'vault_documents/fatura_luz.pdf',
            'expiration_date' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/documents?property_id={$propertyId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'property_id', 'document_category_id', 'file_path']
                ]
            ]);
    }

    public function test_user_can_download_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = DocumentCategory::factory()->create();
        $propertyId = $this->createTestProperty();

        $fileName = 'contrato_arrendamento.pdf';
        Storage::disk('local')->put("vault_documents/{$fileName}", 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => $fileName,
            'description' => 'Contrato',
            'file_path' => "vault_documents/{$fileName}",
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/documents/{$document->id}/download");

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
            'file_path' => "vault_documents/{$fileName}",
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vault_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing("vault_documents/{$fileName}");
    }
}
