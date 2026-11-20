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
use App\Services\Invoice\InvoiceService;
use App\Models\File\File;
use Carbon\Carbon;
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

    private Resident $resident;
    private Property $property;
    private ConsumptionType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');

        // the seeder writes demo benchmarks for a random home; when that home happens to match
        // this test's (Porto, first type and typology) they leak into the results
        DB::table('consumption_benchmarks')->delete();

        $user = $this->signIn();

        $this->resident = Resident::where('user_id', $user->id)->first()
            ?? Resident::factory()->create(['user_id' => $user->id]);

        $this->property = $this->createProperty('Porto');
        $this->giveContract($this->resident, $this->property);
        $this->type = $this->consumptionType('Eletricidade', 'kWh');
    }

    protected function signIn(?User $user = null, bool $withResident = true): User
    {
        $user ??= User::factory()->create();

        if ($withResident) {
            Resident::factory()->create(['user_id' => $user->id]);
        }

        Sanctum::actingAs($user);

        return $user;
    }

    protected function consumptionType(string $name = 'Eletricidade', string $unit = 'kWh'): ConsumptionType
    {
        return ConsumptionType::firstOrCreate(
            ['name' => $name],
            ['unit_of_measure' => $unit]
        );
    }

    private function seededTypes(): array
    {
        $types = ConsumptionType::whereIn('id', [1, 2, 3])
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $types, 'Os tipos de consumo 1, 2 e 3 têm de existir (seed).');

        return $types->all();
    }

    protected function createProperty(string $district = 'Porto'): Property
    {
        $type = PropertyType::first() ?? PropertyType::factory()->create();
        $typology = PropertyTypology::first() ?? PropertyTypology::factory()->create();

        $address = Address::unguarded(fn () => Address::create([
            'street' => 'Rua de Teste',
            'postal_code' => '4000-001',
            'door' => '1',
            'fraction' => '1º A',
            'county' => $district,
            'location' => $district,
            'district' => $district,
        ]));

        return Property::unguarded(fn () => Property::create([
            'property_type_id' => $type->id,
            'property_typology_id' => $typology->id,
            'address_id' => $address->id,
            'condominium_id' => null,
            'area' => 80,
        ]));
    }

    protected function giveContract(Resident $resident, Property $property, bool $active = true): void
    {
        $residentTypeId = DB::table('resident_types')->value('id')
            ?? DB::table('resident_types')->insertGetId(['type' => 'Proprietário']);

        PropertyContract::firstOrCreate(
            [
                'resident_id' => $resident->id,
                'property_id' => $property->id,
            ],
            [
                'start_date' => now()->subYear()->toDateString(),
                'end_date' => null,
                'resident_type_id' => $residentTypeId,
                'is_active' => $active,
            ]
        );
    }

    protected function createInvoiceWithConsumption(
        Resident $resident,
        Property $property,
        ConsumptionType $type,
        string $start = '2025-03-01',
        string $end = '2025-03-31',
        float $amount = 240,
        array $invoiceOverrides = [],
        ?float $cost = null,
    ): Invoice {
        $invoice = Invoice::factory()->create(array_merge([
            'resident_id' => $resident->id,
            'property_id' => $property->id,
            'period_start' => $start,
            'period_end' => $end,
        ], $invoiceOverrides));

        Consumption::factory()->create(array_merge([
            'invoice_id' => $invoice->id,
            'property_id' => $property->id,
            'consumption_type_id' => $type->id,
            'period_start' => $start,
            'period_end' => $end,
            'amount' => $amount,
        ], $cost !== null ? ['cost' => $cost] : []));

        return $invoice;
    }

    private function seedMonthlyCosts(
        ConsumptionType $type,
        int $year,
        int $months,
        float $monthlyCost
    ): void {
        for ($month = 1; $month <= $months; $month++) {
            $start = Carbon::create($year, $month, 1);

            $this->createInvoiceWithConsumption(
                $this->resident,
                $this->property,
                $type,
                $start->toDateString(),
                $start->copy()->endOfMonth()->toDateString(),
                100,
                ['invoice_number' => "T{$type->id}-{$year}-{$month}"],
                $monthlyCost,
            );
        }
    }

    private function benchmarkRow(
        string $period,
        string $start,
        float $averageCost,
        int $sampleSize = 8,
        ?ConsumptionType $type = null,
        ?string $region = null,
    ): void {
        DB::table('consumption_benchmarks')->updateOrInsert(
            [
                'consumption_type_id' => ($type ?? $this->type)->id,
                'property_type_id' => $this->property->property_type_id,
                'typology_id' => $this->property->property_typology_id,
                'region' => $region ?? $this->property->address->district,
                'reference_period' => $period,
                'period_start' => $start,
            ],
            [
                'average_value' => 200,
                'average_cost' => $averageCost,
                'sample_size' => $sampleSize,
            ]
        );
    }

    private function payload(Property $property, array $overrides = []): array
    {
        return array_merge([
            'property_id' => $property->id,
            'invoice_number' => 'FT-0001',
            'issue_date' => '2025-04-10',
            'period_start' => '2025-03-01',
            'period_end' => '2025-03-31',
            'total_amount' => 57.20,
            'supplier' => 'EDP',
            'consumptions' => [
                [
                    'consumption_type_id' => $this->type->id,
                    'amount' => 150,
                    'cost' => 22.5,
                ],
            ],
        ], $overrides);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('fatura.pdf', 100, 'application/pdf');
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/benchmarks/monthly/series')->assertUnauthorized();
        $this->getJson('/api/v1/benchmarks/annual/series')->assertUnauthorized();
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->getJson('/api/v1/consumption-types')->assertUnauthorized();
    }

    public function test_monthly_benchmark_series_returns_the_group_averages(): void
    {
        $this->benchmarkRow('monthly', '2025-03-01', 55.5, 8);

        $response = $this->getJson("/api/v1/benchmarks/monthly/series?property_id={$this->property->id}")
            ->assertOk()
            ->assertJsonCount(1);

        $row = $response->json('0');

        $this->assertSame($this->type->id, $row['consumption_type_id']);
        $this->assertSame('2025-03-01', $row['period_start']);
        $this->assertEqualsWithDelta(55.5, $row['average_cost'], 0.01);
        $this->assertSame(8, $row['sample_size']);
    }

    public function test_monthly_benchmark_series_ignores_other_regions(): void
    {
        $this->benchmarkRow('monthly', '2025-03-01', 55.5, 8, null, 'Lisboa');

        $this->getJson("/api/v1/benchmarks/monthly/series?property_id={$this->property->id}")
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_monthly_benchmark_series_requires_the_property(): void
    {
        $this->getJson('/api/v1/benchmarks/monthly/series')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_monthly_benchmark_series_rejects_a_property_of_someone_else(): void
    {
        $other = $this->createProperty('Lisboa');

        $this->getJson("/api/v1/benchmarks/monthly/series?property_id={$other->id}")
            ->assertNotFound();
    }

    public function test_a_user_without_a_resident_profile_is_forbidden_on_benchmarks(): void
    {
        $this->signIn(null, false);

        $this->getJson("/api/v1/benchmarks/monthly/series?property_id={$this->property->id}")
            ->assertForbidden();

        $this->getJson('/api/v1/benchmarks/annual/series')
            ->assertForbidden();
    }

    public function test_annual_benchmark_series_compares_a_single_service(): void
    {
        [$electricity] = $this->seededTypes();

        $this->seedMonthlyCosts($electricity, 2025, 12, 100);
        $this->benchmarkRow('annual', '2025-01-01', 1100, 8, $electricity);

        $response = $this->getJson("/api/v1/benchmarks/annual/series?property_id={$this->property->id}")
            ->assertOk();

        $response->assertJsonPath('data', []);

        $row = $response->json("services.{$electricity->id}.data.0");

        $this->assertSame(2025, $row['year']);
        $this->assertEqualsWithDelta(1200, $row['user_total'], 0.01);
        $this->assertEqualsWithDelta(1100, $row['benchmark_total'], 0.01);
        $this->assertEqualsWithDelta(100, $row['difference'], 0.01);
        $this->assertSame(12, $row['months_count']);
        $this->assertSame(0, $row['estimated_months_count']);
    }

    public function test_annual_benchmark_series_general_view_needs_the_three_services(): void
    {
        foreach ($this->seededTypes() as $type) {
            $this->seedMonthlyCosts($type, 2025, 12, 100);
            $this->benchmarkRow('annual', '2025-01-01', 1000, 8, $type);
        }

        $response = $this->getJson("/api/v1/benchmarks/annual/series?property_id={$this->property->id}")
            ->assertOk()
            ->assertJsonPath('service_options.0.value', 'all')
            ->assertJsonPath('service_options.0.label', 'Geral')
            ->assertJsonCount(3, 'data.0.services');

        $row = $response->json('data.0');

        $this->assertSame(2025, $row['year']);
        $this->assertEqualsWithDelta(3600, $row['user_total'], 0.01);
        $this->assertEqualsWithDelta(3000, $row['benchmark_total'], 0.01);
        $this->assertEqualsWithDelta(600, $row['difference'], 0.01);
        $this->assertEqualsWithDelta(20, $row['difference_percentage'], 0.01);
    }

    public function test_annual_benchmark_series_estimates_the_missing_months(): void
    {
        [$electricity] = $this->seededTypes();

        $this->seedMonthlyCosts($electricity, 2025, 10, 100);
        $this->benchmarkRow('annual', '2025-01-01', 1000, 8, $electricity);

        $row = $this->getJson("/api/v1/benchmarks/annual/series?property_id={$this->property->id}")
            ->assertOk()
            ->json("services.{$electricity->id}.data.0");

        $this->assertSame(10, $row['months_count']);
        $this->assertSame(2, $row['estimated_months_count']);
        $this->assertEqualsWithDelta(1200, $row['user_total'], 0.01);
    }

    public function test_annual_benchmark_series_ignores_years_with_fewer_than_nine_months(): void
    {
        [$electricity] = $this->seededTypes();

        $this->seedMonthlyCosts($electricity, 2025, 8, 100);
        $this->benchmarkRow('annual', '2025-01-01', 1000, 8, $electricity);

        $this->getJson("/api/v1/benchmarks/annual/series?property_id={$this->property->id}")
            ->assertOk()
            ->assertJsonPath('service_options', [])
            ->assertJsonPath('data', [])
            ->assertJsonPath('services', []);
    }

    public function test_annual_benchmark_series_rejects_a_property_of_someone_else(): void
    {
        $other = $this->createProperty('Lisboa');

        $this->getJson("/api/v1/benchmarks/annual/series?property_id={$other->id}")
            ->assertNotFound();
    }

    public function test_annual_benchmark_series_requires_the_property_when_there_are_several(): void
    {
        $this->giveContract($this->resident, $this->createProperty('Lisboa'));

        $this->getJson('/api/v1/benchmarks/annual/series')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_consumption_types_are_listed(): void
    {
        $this->getJson('/api/v1/consumption-types')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'unit_of_measure']]]);
    }

    public function test_invoice_full_lifecycle_crud(): void
    {
        $response = $this->postJson('/api/v1/invoices', $this->payload($this->property))
            ->assertCreated()
            ->assertJsonPath('data.invoice_number', 'FT-0001');

        $invoiceId = $response->json('data.id');

        $this->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.id', $invoiceId);

        $this->deleteJson("/api/v1/invoices/{$invoiceId}")->assertOk();

        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_invoices_can_be_filtered_by_property(): void
    {
        $second = $this->createProperty('Lisboa');
        $this->giveContract($this->resident, $second);

        $this->createInvoiceWithConsumption(
            $this->resident,
            $this->property,
            $this->type,
            invoiceOverrides: ['invoice_number' => 'A-1'],
        );

        $this->createInvoiceWithConsumption(
            $this->resident,
            $second,
            $this->type,
            invoiceOverrides: ['invoice_number' => 'B-1'],
        );

        $this->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/v1/invoices?property_id={$second->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.property_id', $second->id);
    }

    public function test_an_invoice_without_a_pdf_has_no_file_content(): void
    {
        $invoiceId = $this->postJson('/api/v1/invoices', $this->payload($this->property))
            ->assertCreated()
            ->json('data.id');

        $this->getJson("/api/v1/invoices/{$invoiceId}/file/content")->assertNotFound();
    }

    public function test_reading_a_pdf_only_extracts_the_data_and_stores_nothing(): void
    {
        $this->mock(InvoiceExtractionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extract')->once()->andReturn([
                'supplier' => 'EDP',
                'invoice_number' => 'FT-999',
                'issue_date' => '2026-09-12',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'total_amount' => 50.0,
                'consumptions' => [],
            ]);
        });
        $filesBefore = File::count();

        $this->postJson('/api/v1/invoices/upload', ['file' => $this->pdf()])
            ->assertOk()
            ->assertJsonPath('data.extracted.invoice_number', 'FT-999')
            ->assertJsonMissingPath('data.file_id');

        // closing the form without saving leaves nothing behind
        $this->assertSame($filesBefore, File::count());
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_the_pdf_is_stored_only_when_the_invoice_is_saved(): void
    {
        $invoiceId = $this->postJson(
            '/api/v1/invoices',
            $this->payload($this->property, ['file' => $this->pdf()])
        )
            ->assertCreated()
            ->assertJsonPath('data.has_file', true)
            ->json('data.id');

        $file = Invoice::findOrFail($invoiceId)->file;

        $this->assertSame(auth()->id(), $file->user_id);
        $this->assertSame(InvoiceService::FILE_CATEGORY, $file->category);

        // the PDF is read through the API: no link to the bucket is ever handed out
        $content = $this->get("/api/v1/invoices/{$invoiceId}/file/content")->assertOk();

        $this->assertSame(Storage::disk('r2')->get($file->path), $content->streamedContent());
    }

    public function test_an_invoice_that_fails_validation_stores_no_pdf(): void
    {
        $filesBefore = File::count();

        $this->postJson('/api/v1/invoices', $this->payload($this->property, [
            'file' => $this->pdf(),
            'consumptions' => [],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consumptions']);

        $this->assertSame($filesBefore, File::count());
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_only_a_pdf_can_be_attached(): void
    {
        $this->postJson('/api/v1/invoices', $this->payload($this->property, [
            'file' => UploadedFile::fake()->create('fatura.html', 10, 'text/html'),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'A fatura tem de ser um ficheiro PDF.']);
    }

    public function test_deleting_an_invoice_deletes_its_pdf(): void
    {
        $invoiceId = $this->postJson(
            '/api/v1/invoices',
            $this->payload($this->property, ['file' => $this->pdf()])
        )
            ->assertCreated()
            ->json('data.id');

        $file = Invoice::findOrFail($invoiceId)->file;

        $this->deleteJson("/api/v1/invoices/{$invoiceId}")->assertOk();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);

        $this->assertFalse(
            Storage::disk('r2')->exists($file->path),
            'O PDF deveria ter sido removido do disco R2.'
        );
    }
}
