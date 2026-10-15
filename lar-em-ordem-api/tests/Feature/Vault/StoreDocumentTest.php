<?php

namespace Tests\Feature\Vault;

use App\Models\Property\Property;
use App\Models\User\User;
use App\Models\Vault\DocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoreDocumentTest extends TestCase
{
    public function test_can_upload_document_with_manual_dates(): void
    {
        // Criar as dependências na BD de testes usando Factories
        $user = User::factory()->create();

        $property = Property::factory()->create();

        $category = DocumentCategory::factory()->create();

        $file = UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf');

        // Passar os IDs dinâmicos gerados pelas factories em vez de strings fixas
        $response = $this->actingAs($user)->postJson('/api/v1/documents', [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'description' => 'Certificado de Teste',
            'file' => $file,
            'issue_date' => '2026-01-01',
            'expiration_date' => '2028-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.expiration_date', '2028-01-01');
    }
}
