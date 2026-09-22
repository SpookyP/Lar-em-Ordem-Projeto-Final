<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User;
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

    public function test_user_can_upload_pdf_and_extract_data(): void
    {
        // Simular o disco local para não guardar ficheiros reais durante os testes
        Storage::fake('local');

        // Fazer Mock do serviço Python para devolver um array estático
        $this->mock(PdfExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractInfo')
                ->once()
                ->andReturn([
                    'issue_date' => '2023-01-01',
                    'expiration_date' => '2030-12-31',
                    'extracted_text' => 'Mocked text from PDF'
                ]);
        });

        // Criar os dados de suporte na base de dados
        $user = User::factory()->create();

        // Inserir uma Habitação (Property) simulada diretamente na base de dados
        $propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => 1,
            'property_typology_id' => 1,
            'address_id' => 1,
            'area' => 100,
            'fraction' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = DocumentCategory::factory()->create();

        // Criar um ficheiro PDF em memória
        $file = UploadedFile::fake()->create('energy_certificate.pdf', 100, 'application/pdf');

        // Executar o pedido POST autenticado com Sanctum
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'description' => 'Test document payload',
            'file' => $file,
        ]);

        // Afirmar que a resposta HTTP é 201 Created e verificar a estrutura JSON
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

        // Afirmar que o registo foi inserido na tabela correta
        $this->assertDatabaseHas('vault_documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'name' => 'energy_certificate.pdf',
            'expiration_date' => '2030-12-31 00:00:00',
        ]);

        // Afirmar que o ficheiro foi movido para a pasta correta no disco simulado
        Storage::disk('local')->assertExists('vault_documents/' . $file->hashName());
    }

    public function test_upload_fails_if_file_is_not_pdf(): void
    {
        $user = User::factory()->create();

        // Aplicar o mesmo workaround do primeiro teste
        Schema::disableForeignKeyConstraints();

        $propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => 1,
            'property_typology_id' => 1,
            'address_id' => 1,
            'area' => 100,
            'fraction' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::enableForeignKeyConstraints();

        $category = DocumentCategory::factory()->create();

        // Enviar uma imagem em vez de um PDF
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/documents', [
            'property_id' => $propertyId,
            'document_category_id' => $category->id,
            'file' => $file,
        ]);

        // Bloquear o pedido com erro 422 Unprocessable Entity
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
