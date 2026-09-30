<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Vault\DocumentCategory;
use App\Services\Vault\PdfExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

class DocumentTest extends TestCase
{
    // Limpa e recria a base de dados a cada teste, garantindo um estado limpo
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Corre os seeders do Rafa para popular a base de dados com Users, Properties, etc.
        $this->seed();
    }

    /**
     * Testa o "Caminho Feliz" (Happy Path).
     * Garante que um utilizador autenticado consegue fazer upload de um PDF,
     * que o serviço de extração é chamado corretamente e que os dados
     * são guardados na base de dados e o ficheiro no disco.
     *
     * @return void
     */
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

        // Obter dados gerados pelos Seeders em vez de os criar manualmente
        $user = User::first();
        $propertyId = DB::table('properties')->first()->id;
        $category = DocumentCategory::first();

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

    /**
     * Testa a validação de ficheiros.
     * Garante que o sistema rejeita qualquer ficheiro que não seja um PDF
     * devolvendo um erro 422 de validação.
     *
     * @return void
     */
    public function test_upload_fails_if_file_is_not_pdf(): void
    {
        // Usar dados dos Seeders
        $user = User::first();
        $propertyId = DB::table('properties')->first()->id;
        $category = DocumentCategory::first();

        // Enviar uma imagem em vez de um PDF
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
        $user = User::first();
        $propertyId = DB::table('properties')->first()->id;
        $category = DocumentCategory::first();

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

        $user = User::first();
        $propertyId = DB::table('properties')->first()->id;
        $category = DocumentCategory::first();

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

        $user = User::first();
        $propertyId = DB::table('properties')->first()->id;
        $category = DocumentCategory::first();

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
