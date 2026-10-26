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
 * Catálogos (módulo 6), preenchidos pelos seeders: categorias de problemas com as especialidades
 * e zonas de serviço (distrito → concelho → localidade).
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

    public function test_categorias_exigem_autenticacao(): void
    {
        $this->getJson('/api/v1/problem-categories')->assertUnauthorized();
    }

    public function test_lista_todas_as_categorias_com_as_especialidades_por_ordem_alfabetica(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/problem-categories')
            ->assertOk()
            ->assertJsonCount(ProblemCategory::count(), 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'specialties' => [['id', 'problem_category_id', 'name']]]]]);

        // a ordem é comparada com a da própria base de dados (acentos ordenam de forma diferente em MySQL e SQLite)
        $this->assertSame(
            ProblemCategory::orderBy('name')->pluck('name')->all(),
            collect($resposta->json('data'))->pluck('name')->all()
        );

        foreach ($resposta->json('data') as $categoria) {
            $especialidades = collect($categoria['specialties']);

            // todas pertencem a esta categoria e vêm por ordem alfabética
            $this->assertTrue($especialidades->every(fn ($e) => $e['problem_category_id'] === $categoria['id']));
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

    public function test_zonas_exigem_autenticacao(): void
    {
        $this->getJson('/api/v1/service-zones')->assertUnauthorized();
    }

    public function test_sem_filtros_devolve_o_catalogo_inteiro_ordenado(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones')
            ->assertOk()
            ->assertJsonCount(ServiceZone::count(), 'data')
            ->assertJsonStructure(['data' => [['id', 'district', 'county', 'location']]]);

        // ordenado por distrito, concelho e localidade (comparado com a ordem da base de dados)
        $esperado = ServiceZone::orderBy('district')->orderBy('county')->orderBy('location')->pluck('id')->all();

        $this->assertSame($esperado, collect($resposta->json('data'))->pluck('id')->all());
    }

    public function test_filtra_por_distrito(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=Porto')
            ->assertOk()
            ->assertJsonCount(ServiceZone::where('district', 'Porto')->count(), 'data');

        $this->assertTrue(collect($resposta->json('data'))->every(fn ($z) => $z['district'] === 'Porto'));
    }

    public function test_filtra_por_distrito_e_concelho(): void
    {
        $resposta = $this->actingAs($this->user)
            ->getJson('/api/v1/service-zones?district=Porto&county=Matosinhos')
            ->assertOk()
            ->assertJsonCount(ServiceZone::where('district', 'Porto')->where('county', 'Matosinhos')->count(), 'data');

        $localidades = collect($resposta->json('data'))->pluck('location');
        $this->assertTrue($localidades->contains('Senhora da Hora'));
        $this->assertTrue(collect($resposta->json('data'))->every(fn ($z) => $z['county'] === 'Matosinhos'));
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