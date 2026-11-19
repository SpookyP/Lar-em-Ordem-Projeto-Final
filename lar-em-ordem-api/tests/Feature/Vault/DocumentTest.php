<?php

namespace Tests\Feature\Vault;

use App\Models\Condominium\Condominium;
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
use Illuminate\Support\Facades\DB;
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
            'name' => 'Certificado de Teste',
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

        $file = UploadedFile::fake()->create('documento.txt', 100, 'text/plain');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/properties/{$property->id}/documents", [
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => 'Documento Falso',
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_authorized_user_can_list_property_documents(): void
    {
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        $file = \App\Models\File\File::factory()->create([
            'user_id' => $user->id
        ]);

        \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => 'caderneta_predial.pdf',
            'description' => 'Caderneta Predial da Autoridade Tributária',
            'file_id' => $file->id,
            'expiration_date' => now()->addMonths(6),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/properties/{$property->id}/documents");

        $response->assertStatus(200);
    }

    public function test_the_file_is_read_through_the_api_only_by_who_has_access(): void
    {
        Storage::fake('r2');
        [$user, $property] = $this->createAuthorizedUserAndProperty();

        $file = \App\Models\File\File::factory()->create([
            'user_id' => $user->id,
            'path' => 'vault_documents/contrato.pdf',
        ]);
        Storage::disk('r2')->put($file->path, 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => DocumentCategory::factory()->create()->id,
            'name' => 'contrato.pdf',
            'description' => 'Contrato',
            'file_id' => $file->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/documents/{$document->id}/content")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame('conteudo falso do pdf', $response->streamedContent());

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/documents/{$document->id}/content")
            ->assertForbidden();
    }

    public function test_authorized_user_can_delete_document(): void
    {
        // Usa a Cloud (R2) em vez do disco local para o Fake
        Storage::fake('r2');
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = DocumentCategory::factory()->create();

        // O ficheiro precisa de ter um ID e path realistas
        $file = \App\Models\File\File::factory()->create([
            'user_id' => $user->id,
            'path' => 'vault_documents/teste_planta.pdf'
        ]);

        // Cria o ficheiro virtual na Cloud
        Storage::disk('r2')->put($file->path, 'conteudo falso do pdf');

        $document = \App\Models\Vault\Document::create([
            'property_id' => $property->id,
            'document_category_id' => $category->id,
            'name' => 'planta_casa.pdf',
            'description' => 'Planta',
            'file_id' => $file->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vault_documents', ['id' => $document->id]);

        Storage::disk('r2')->assertMissing($file->path);
    }

    public function test_unauthorized_user_cannot_access_documents_of_another_property(): void
    {
        [$owner, $property] = $this->createAuthorizedUserAndProperty();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/v1/properties/{$property->id}/documents");

        $response->assertStatus(403);
    }

    public function test_deleting_document_only_deletes_its_own_notifications_and_not_similar_named_ones(): void
    {
        [$user, $property] = $this->createAuthorizedUserAndProperty();
        $category = \App\Models\Vault\DocumentCategory::factory()->create();

        // Criar documento "Seguro" a expirar em 5 dias
        $resSeguro = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->id}/documents", [
                'property_id' => $property->id,
                'document_category_id' => $category->id,
                'name' => 'Seguro',
                'expiration_date' => now()->addDays(5)->toDateString(),
            ]);
        $seguroId = $resSeguro->json('data.id');

        // Criar documento "Seguro Casa" a expirar em 5 dias
        $resSeguroCasa = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/properties/{$property->id}/documents", [
                'property_id' => $property->id,
                'document_category_id' => $category->id,
                'name' => 'Seguro Casa',
                'expiration_date' => now()->addDays(5)->toDateString(),
            ]);
        $seguroCasaId = $resSeguroCasa->json('data.id');

        // Confirmar que ambos criaram alerta na BD
        $this->assertDatabaseHas('vault_notifications', ['document_id' => $seguroId]);
        $this->assertDatabaseHas('vault_notifications', ['document_id' => $seguroCasaId]);

        // Apagar APENAS o documento "Seguro"
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/documents/{$seguroId}")
            ->assertStatus(200);

        // Confirmar que o alerta do "Seguro" foi apagado, mas o do "Seguro Casa" continua intacto!
        $this->assertDatabaseMissing('vault_notifications', ['document_id' => $seguroId]);
        $this->assertDatabaseHas('vault_notifications', ['document_id' => $seguroCasaId]);
    }

    public function test_tenant_cannot_access_vault_documents_or_notifications(): void
    {
        $propertyType = PropertyType::query()->first();

        if (! $propertyType) {
            $propertyType = new PropertyType();
            $propertyType->type = 'Apartamento';
            $propertyType->save();
        }

        $propertyTypology = PropertyTypology::query()->first();

        if (! $propertyTypology) {
            $propertyTypology = new PropertyTypology();
            $propertyTypology->typology = 'T2';
            $propertyTypology->save();
        }

        $condominium = Condominium::factory()->create();

        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id,
            'property_typology_id' => $propertyTypology->id,
            'condominium_id' => $condominium->id,
        ]);

        $tenantUser = User::factory()->create();
        $tenantResident = Resident::factory()->create(['user_id' => $tenantUser->id]);

        $tenantType = ResidentType::query()->where('type', 'Tenant')->first();

        if (! $tenantType) {
            $tenantType = new ResidentType();
            $tenantType->type = 'Tenant';
            $tenantType->save();
        }

        PropertyContract::factory()->create([
            'property_id' => $property->id,
            'resident_id' => $tenantResident->id,
            'resident_type_id' => $tenantType->id,
            'is_active' => true,
        ]);

        $category = DocumentCategory::factory()->create();

        $this->actingAs($tenantUser)
            ->getJson("/api/v1/properties/{$property->id}/documents")
            ->assertForbidden();

        $this->actingAs($tenantUser)
            ->postJson("/api/v1/properties/{$property->id}/documents", [
                'document_category_id' => $category->id,
                'name' => 'Documento Inquilino',
            ])
            ->assertForbidden();

        $this->actingAs($tenantUser)
            ->getJson("/api/v1/properties/{$property->id}/notifications")
            ->assertForbidden();
    }
}
