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
use App\Services\Vault\PdfExtractionService; // <-- Adicionar
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface; // <-- Adicionar

class StoreDocumentTest extends TestCase
{
    use RefreshDatabase;

    // ... (Mantém a função createAuthorizedUserAndProperty igual) ...

    public function test_can_upload_document_with_manual_dates(): void
    {
        Storage::fake('r2');

        $this->mock(PdfExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractInfo')->andReturn([]);
        });

        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $file = UploadedFile::fake()->create('manual_dates.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/properties/{$property->id}/documents", [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => 'Documento Manual Teste',
            'file' => $file,
            'issue_date' => '2026-01-01',
            'expiration_date' => '2028-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.expiration_date', '2028-01-01');
    }
}
