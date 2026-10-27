<?php

namespace Tests\Feature\Invoice;

use App\Models\Invoice\Consumption;
use App\Models\Invoice\ConsumptionType;
use App\Models\Invoice\Invoice;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\Resident;
use App\Models\User\User;
use App\Services\Invoice\InvoiceExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;
    private Property $property;
    private ConsumptionType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');

        $this->user = $this->signIn();
        $this->property = $this->createProperty('Porto');
        $this->giveContract($this->user, $this->property);
        $this->type = $this->consumptionType('Eletricidade', 'kWh');
    }

    // -------------------------------------------------------------------------
    // HELPERS DE APOIO
    // -------------------------------------------------------------------------

    protected function signIn(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function consumptionType(string $name = 'Eletricidade', string $unit = 'kWh'): ConsumptionType
    {
        return ConsumptionType::firstOrCreate(['name' => $name], ['unit_of_measure' => $unit]);
    }

    protected function createProperty(string $district = 'Porto'): Property
    {
        $type = PropertyType::first() ?? PropertyType::factory()->create();
        $typology = PropertyTypology::first() ?? PropertyTypology::factory()->create();

        $address = Address::unguarded(fn () => Address::create([
            'street'      => 'Rua de Teste',
            'postal_code' => '4000-001',
            'door'        => '1',
            'fraction'    => '1º A',
            'county'      => $district,
            'location'    => $district,
            'district'    => $district,
        ]));

        return Property::unguarded(fn () => Property::create([
            'property_type_id'     => $type->id,
            'property_typology_id' => $typology->id,
            'address_id'           => $address->id,
            'condominium_id'       => null,
            'area'                 => 80,
        ]));
    }

    protected function giveContract(User $user, Property $property, bool $active = true): void
    {
        $resident = Resident::unguarded(fn () => Resident::firstOrCreate(
            ['user_id' => $user->id],
            ['nif' => fake()->numerify('2########'), 'is_active' => true]
        ));

        $residentTypeId = DB::table('resident_types')->value('id')
            ?? DB::table('resident_types')->insertGetId(['type' => 'Proprietário']);

        PropertyContract::firstOrCreate(
            [
                'resident_id' => $resident->id,
                'property_id' => $property->id,
            ],
            [
                'start_date'       => now()->subYear()->toDateString(),
                'end_date'         => null,
                'resident_type_id' => $residentTypeId,
                'is_active'        => $active,
            ]
        );
    }

    protected function createInvoiceWithConsumption(
        User $user,
        Property $property,
        ConsumptionType $type,
        string $start = '2025-03-01',
        string $end = '2025-03-31',
        float $amount = 240,
        array $invoiceOverrides = [],
    ): Invoice {
        $invoice = Invoice::factory()->create(array_merge([
            'user_id'      => $user->id,
            'property_id'  => $property->id,
            'period_start' => $start,
            'period_end'   => $end,
        ], $invoiceOverrides));

        Consumption::factory()->create([
            'invoice_id'          => $invoice->id,
            'property_id'         => $property->id,
            'consumption_type_id' => $type->id,
            'period_start'        => $start,
            'period_end'          => $end,
            'amount'              => $amount,
        ]);

        return $invoice;
    }

    private function payload(Property $property, array $overrides = []): array
    {
        return array_merge([
            'property_id'    => $property->id,
            'invoice_number' => 'FT-0001',
            'issue_date'     => '2025-04-10',
            'period_start'   => '2025-03-01',
            'period_end'     => '2025-03-31',
            'total_amount'   => 57.20,
            'supplier'       => 'EDP',
            'consumptions'   => [
                ['consumption_type_id' => $this->type->id, 'amount' => 150, 'cost' => 22.5],
            ],
        ], $overrides);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('fatura.pdf', 100, 'application/pdf');
    }

    // -------------------------------------------------------------------------
    // TESTES DE SEGURANÇA E AUTENTICAÇÃO
    // -------------------------------------------------------------------------

    public function test_endpoints_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/benchmarks/monthly')->assertUnauthorized();
        $this->getJson('/api/v1/benchmarks/annual')->assertUnauthorized();
        $this->getJson('/api/v1/consumptions')->assertUnauthorized();
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->getJson('/api/v1/consumption-types')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // TESTES DE BENCHMARKS (MENSAL E ANUAL)
    // -------------------------------------------------------------------------

    public function test_monthly_and_annual_benchmarks(): void
    {
        $this->createInvoiceWithConsumption($this->user, $this->property, $this->type, '2025-03-01', '2025-03-31', 240);
        
        DB::table('consumption_benchmarks')->insert([
            'consumption_type_id' => $this->type->id,
            'property_type_id'    => $this->property->property_type_id,
            'typology_id'         => $this->property->property_typology_id,
            'region'              => $this->property->address->district,
            'reference_period'    => 'monthly',
            'period_start'        => '2025-03-01',
            'average_value'       => 200,
            'sample_size'         => 8,
        ]);

        $this->getJson("/api/v1/benchmarks/monthly?month=2025-03&consumption_type_id={$this->type->id}&property_id={$this->property->id}")
            ->assertOk()
            ->assertJsonPath('reference_period', 'monthly');

        $this->getJson("/api/v1/benchmarks/annual?year=2025&consumption_type_id={$this->type->id}&property_id={$this->property->id}")
            ->assertOk();
    }

    public function test_user_without_resident_gets_404_on_benchmarks(): void
    {
        $this->signIn(User::factory()->create());
        $this->getJson("/api/v1/benchmarks/monthly?month=2025-03&consumption_type_id={$this->type->id}")->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // TESTES DE TIPOS DE CONSUMO (CONSUMPTION TYPES)
    // -------------------------------------------------------------------------

    public function test_consumption_types_are_listed(): void
    {
        $this->getJson('/api/v1/consumption-types')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'unit_of_measure']]]);
    }

    // -------------------------------------------------------------------------
    // TESTES DE CONSUMOS (CONSUMPTIONS)
    // -------------------------------------------------------------------------

    public function test_consumptions_index_filter_and_show(): void
    {
        $invoice = $this->createInvoiceWithConsumption($this->user, $this->property, $this->type);
        $consumption = $invoice->consumptions()->first();

        // Listar todos
        $this->getJson('/api/v1/consumptions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Filtrar por propriedade
        $this->getJson("/api/v1/consumptions?property_id={$this->property->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Mostrar detalhe
        $this->getJson("/api/v1/consumptions/{$consumption->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $consumption->id);
    }

    // -------------------------------------------------------------------------
    // TESTES DE FATURAS (INVOICES CRUD E UPLOAD)
    // -------------------------------------------------------------------------

    public function test_invoice_full_lifecycle_crud(): void
    {
        // 1. Criar Fatura (Store)
        $response = $this->postJson('/api/v1/invoices', $this->payload($this->property))
            ->assertCreated()
            ->assertJsonPath('data.invoice_number', 'FT-0001');
        
        $invoiceId = $response->json('data.id');

        // 2. Listar Faturas (Index)
        $this->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // 3. Ver Fatura (Show)
        $this->getJson("/api/v1/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.id', $invoiceId);

        // 4. Apagar Fatura (Destroy)
        $this->deleteJson("/api/v1/invoices/{$invoiceId}")->assertOk();
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_invoice_upload_and_file_url_flow(): void
    {
        $user = $this->signIn();
        
        $this->mock(InvoiceExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extract')->once()->andReturn([
                'supplier'       => 'EDP',
                'invoice_number' => 'FT-999',
                'issue_date'     => '2026-09-12',
                'period_start'   => '2026-08-01',
                'period_end'     => '2026-08-31',
                'total_amount'   => 50.0,
                'consumptions'   => [],
            ]);
        });

        // Fazer Upload do PDF
        $response = $this->postJson('/api/v1/invoices/upload', ['file' => $this->pdf()])
            ->assertCreated();

        $fileId = $response->json('data.file_id');
        Storage::disk('r2')->assertExists("pending/{$user->id}/{$fileId}.pdf");

        // Criar fatura associando o file_id do upload
        $invoiceResponse = $this->postJson('/api/v1/invoices', $this->payload($this->property, ['file_id' => $fileId]))
            ->assertCreated()
            ->assertJsonPath('data.has_file', true);

        $invoiceId = $invoiceResponse->json('data.id');

        // Obter URL temporário do ficheiro PDF
        $this->getJson("/api/v1/invoices/{$invoiceId}/file")
            ->assertOk()
            ->assertJsonStructure(['data' => ['url']]);
    }
}