<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Catalogs (module 6), filled by the seeders: problem categories with their specialties
 * and service zones (district → county → locality).
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->user = User::where('email', 'morador@example.com')->firstOrFail();
    }

    // ---------------------------------------------------- problem categories

    public function test_guests_can_read_the_categories(): void
    {
        // public on purpose: the sign-up form needs them before there is a session
        $this->getJson('/api/v1/problem-categories')
            ->assertOk()
            ->assertJsonCount(ProblemCategory::count(), 'data');
    }

    public function test_guests_cannot_write_to_the_categories(): void
    {
        $this->postJson('/api/v1/problem-categories', ['name' => 'X'])->assertMethodNotAllowed();
    }

    public function test_lista_todas_as_categorias_com_as_especialidades_por_ordem_alfabetica(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/problem-categories')
            ->assertOk()
            ->assertJsonCount(ProblemCategory::count(), 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'specialties' => [['id', 'problem_category_id', 'name']]]]]);

        // the order is compared with the database's own (accents sort differently in MySQL and SQLite)
        $this->assertSame(
            ProblemCategory::orderBy('name')->pluck('name')->all(),
            collect($resposta->json('data'))->pluck('name')->all()
        );

        foreach ($resposta->json('data') as $categoria) {
            $especialidades = collect($categoria['specialties']);

            // all belong to this category and come in alphabetical order
            $this->assertTrue($especialidades->every(fn($e) => $e['problem_category_id'] === $categoria['id']));
            $this->assertSame(
                ServiceSpecialty::where('problem_category_id', $categoria['id'])->orderBy('name')->pluck('name')->all(),
                $especialidades->pluck('name')->all()
            );
        }
    }

    public function test_cada_categoria_traz_todas_as_suas_especialidades(): void
    {
        $canalizacao = ProblemCategory::where('name', 'Canalização')->firstOrFail();

        $categoria = collect(
            $this->actingAs($this->user)->getJson('/api/v1/problem-categories')->json('data')
        )->firstWhere('id', $canalizacao->id);

        $this->assertCount(
            ServiceSpecialty::where('problem_category_id', $canalizacao->id)->count(),
            $categoria['specialties']
        );
    }

    public function test_a_rota_das_especialidades_de_uma_categoria_ja_nao_existe(): void
    {
        $id = ProblemCategory::value('id');

        $this->actingAs($this->user)
            ->getJson("/api/v1/problem-categories/{$id}/specialties")
            ->assertNotFound();
    }

    // ---------------------------------------------------------- service zones

    public function test_guests_can_read_the_zones(): void
    {
        $this->getJson('/api/v1/service-zones')
            ->assertOk()
            ->assertJsonCount(ServiceZone::count(), 'data');
    }

    public function test_guests_cannot_write_to_the_zones(): void
    {
        $this->postJson('/api/v1/service-zones', ['district' => 'X'])->assertMethodNotAllowed();
    }

    public function test_sem_filtros_devolve_o_catalogo_inteiro_ordenado(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones')
            ->assertOk()
            ->assertJsonCount(ServiceZone::count(), 'data')
            ->assertJsonStructure(['data' => [['id', 'district', 'county', 'location']]]);

        // sorted by district, county and locality (compared with the database order)
        $esperado = ServiceZone::orderBy('district')->orderBy('county')->orderBy('location')->pluck('id')->all();

        $this->assertSame($esperado, collect($resposta->json('data'))->pluck('id')->all());
    }

    public function test_filtra_por_distrito(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=Porto')
            ->assertOk()
            ->assertJsonCount(ServiceZone::where('district', 'Porto')->count(), 'data');

        $this->assertTrue(collect($resposta->json('data'))->every(fn($z) => $z['district'] === 'Porto'));
    }

    public function test_filtra_por_distrito_e_concelho(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=Porto&county=Matosinhos')
            ->assertOk()
            ->assertJsonCount(ServiceZone::where('district', 'Porto')->where('county', 'Matosinhos')->count(), 'data');

        $localidades = collect($resposta->json('data'))->pluck('location');
        $this->assertTrue($localidades->contains('Senhora da Hora'));
        $this->assertTrue(collect($resposta->json('data'))->every(fn($z) => $z['county'] === 'Matosinhos'));
    }

    public function test_um_filtro_sem_resultados_devolve_uma_lista_vazia(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=Atlantida')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_os_filtros_sao_validados(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=' . str_repeat('a', 256))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['district']);
    }

    public function test_a_rota_de_uma_zona_ja_nao_existe(): void
    {
        $id = ServiceZone::value('id');

        $this->actingAs($this->user)
            ->getJson("/api/v1/service-zones/{$id}")
            ->assertNotFound();
    }
}