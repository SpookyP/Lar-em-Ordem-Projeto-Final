<?php

namespace Tests\Feature\Vault;

use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\Resident;
use App\Models\User\ResidentType;
use App\Models\User\User;
use App\Models\Vault\DocumentCategory;
use App\Services\Vault\PdfExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class StoreDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_document_with_manual_dates(): void
    {
        Storage::fake('r2');

        $this->mock(PdfExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractInfo')
                ->zeroOrMoreTimes()
                ->andReturn([
                    'issue_date' => null,
                    'expiration_date' => null,
                    'extracted_text' => 'Mocked text from PDF',
                ]);
        });

        $this->seed([
            \Database\Seeders\PropertyTypeSeeder::class,
            \Database\Seeders\PropertyTypologySeeder::class,
            \Database\Seeders\ResidentTypeSeeder::class,
        ]);

        $user = User::factory()->create();
        $resident = Resident::factory()->create(['user_id' => $user->id]);

        $property = Property::factory()->create([
            'property_type_id' => PropertyType::first()->id,
            'property_typology_id' => PropertyTypology::first()->id,
        ]);

        PropertyContract::factory()->create([
            'resident_id' => $resident->id,
            'property_id' => $property->id,
            'resident_type_id' => ResidentType::first()->id,
            'is_active' => true,
        ]);

        $category = DocumentCategory::factory()->create();
        $file = UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->id}/documents", [
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
