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

class StoreDocumentTest extends TestCase
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
