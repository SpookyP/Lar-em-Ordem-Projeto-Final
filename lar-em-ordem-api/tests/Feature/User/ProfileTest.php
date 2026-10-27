<?php

namespace Tests\Feature\User;

use App\Models\Offer\Offer;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Perfis (residente, prestador, parceiro) criados, atualizados e removidos pelo ProfileService:
 * registo, POST/DELETE /api/user/profile, PUT /user/profile-information e DELETE /api/v1/resident/profile.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        // a regra de password "uncompromised" consulta um serviço externo: nos testes não sai para a internet
        Http::fake();
    }

    private function zonas(int $quantas): array
    {
        return ServiceZone::where('county', 'Matosinhos')->limit($quantas)->pluck('id')->all();
    }

    private function password(): string
    {
        return 'Lr-' . Str::random(16) . '#9a';
    }

    // ------------------------------------------------------------- criação

    public function test_adicionar_perfil_de_prestador_grava_zonas_e_especialidades(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/user/profile', [
                'role'                  => 'service_provider',
                'company_name'          => 'Reparações Lda',
                'nif'                   => '123456789',
                'phone'                 => '912345678',
                'description'           => 'Pequenas reparações.',
                'zone_ids'              => $this->zonas(2),
                'service_specialty_ids' => ServiceSpecialty::limit(2)->pluck('id')->all(),
                'user_id'               => 'outro', // ignorado
            ])
            ->assertCreated()
            ->assertJsonPath('user.service_provider.company_name', 'Reparações Lda');

        $prestador = $user->fresh()->service_provider;
        $this->assertCount(2, $prestador->zones);
        $this->assertCount(2, $prestador->specialties);
        $this->assertSame($user->id, $prestador->user_id);
    }

    public function test_registo_como_prestador_grava_zonas(): void
    {
        $password = $this->password();

        $this->postJson('/register', [
            'name'                  => 'Novo Prestador',
            'email'                 => 'novo@example.com',
            'password'              => $password,
            'password_confirmation' => $password,
            'role'                  => 'service_provider',
            'company_name'          => 'Novo Lda',
            'nif'                   => '123456789',
            'phone'                 => '912345678',
            'description'           => 'Serviços.',
            'zone_ids'              => $this->zonas(1),
        ])->assertSuccessful();

        $user = User::where('email', 'novo@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('service_provider'));
        $this->assertCount(1, $user->service_provider->zones);
    }

    public function test_registo_como_residente(): void
    {
        $password = $this->password();

        $this->postJson('/register', [
            'name'                  => 'Novo Residente',
            'email'                 => 'res@example.com',
            'password'              => $password,
            'password_confirmation' => $password,
            'role'                  => 'resident',
            'nif'                   => '212345678',
        ])->assertSuccessful();

        $user = User::where('email', 'res@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('resident'));
        $this->assertSame('212345678', $user->resident->nif);
    }

    public function test_registo_numa_conta_que_ja_tem_esse_perfil_da_422(): void
    {
        $password = $this->password();
        $user = User::factory()->create(['email' => 'dup@example.com', 'password' => bcrypt($password)]);
        $user->assignRole('resident');
        $user->resident()->create(['nif' => '212345678']);

        $this->postJson('/register', [
            'name'                  => 'Repetido',
            'email'                 => 'dup@example.com',
            'password'              => $password,
            'password_confirmation' => $password,
            'role'                  => 'resident',
            'nif'                   => '212345678',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    // ---------------------------------------------------------- atualização

    public function test_atualizar_prestador_grava_zonas_e_ignora_user_id(): void
    {
        $servicos = User::where('email', 'service@example.com')->firstOrFail();
        $morador  = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($servicos)
            ->putJson('/user/profile-information', [
                'role'     => 'service_provider',
                'name'     => 'Novo Nome',
                'phone'    => '911111111',
                'zone_ids' => $this->zonas(1),
                'user_id'  => $morador->id, // ignorado
            ])
            ->assertOk();

        $prestador = $servicos->service_provider()->first();
        $this->assertSame($servicos->id, $prestador->user_id);
        $this->assertSame('911111111', $prestador->phone);
        $this->assertCount(1, $prestador->zones);
        $this->assertSame('Novo Nome', $servicos->fresh()->name);
    }

    public function test_atualizar_residente(): void
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($morador)
            ->putJson('/user/profile-information', ['role' => 'resident', 'nif' => '298765432'])
            ->assertOk();

        $this->assertSame('298765432', $morador->resident()->first()->nif);
    }

    // ------------------------------------------------------------- remoção

    public function test_apagar_perfil_de_parceiro_apaga_as_ofertas(): void
    {
        $parceiro = User::where('email', 'parceiro@example.com')->firstOrFail();
        $id = $parceiro->partner->id;
        $parceiro->assignRole('resident'); // para não ser o único perfil

        $this->actingAs($parceiro)
            ->deleteJson('/api/user/profile', ['role' => 'partner'])
            ->assertOk();

        $this->assertSame(0, Offer::where('partner_id', $id)->count());
        $this->assertFalse($parceiro->fresh()->hasRole('partner'));
    }

    public function test_nao_se_apaga_o_unico_perfil_nem_um_perfil_que_nao_existe(): void
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($morador)
            ->deleteJson('/api/user/profile', ['role' => 'resident'])
            ->assertStatus(422)
           ->assertJsonValidationErrors(['role' => 'Não pode remover o seu único perfil. Elimine a conta.']);

        $this->actingAs($morador)
            ->deleteJson('/api/user/profile', ['role' => 'partner'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role' => 'Não tem um perfil de partner associado a esta conta.']);
    }

    // ------------------------------------------ DELETE /api/v1/resident/profile

    public function test_apagar_o_perfil_de_residente_pelo_endpoint_do_residente(): void
    {
        $user = User::where('email', 'residente@example.com')->firstOrFail(); // residente + prestador
        $id = $user->resident->id;

        $this->actingAs($user)
            ->deleteJson('/api/v1/resident/profile')
            ->assertOk()
            ->assertJsonPath('message', 'Resident profile removed successfully.');

        $this->assertSoftDeleted('residents', ['id' => $id]);
        $this->assertFalse($user->fresh()->hasRole('resident'));

        // já não tem perfil de residente
        $this->actingAs($user)->deleteJson('/api/v1/resident/profile')->assertNotFound();
    }

    public function test_o_endpoint_do_residente_nao_apaga_o_unico_perfil(): void
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($morador)->deleteJson('/api/v1/resident/profile')->assertStatus(422);

        $this->assertNotNull($morador->resident()->first());
    }

    public function test_o_endpoint_do_residente_sem_perfil_da_404(): void
    {
        $servicos = User::where('email', 'service@example.com')->firstOrFail();

        $this->actingAs($servicos)->deleteJson('/api/v1/resident/profile')->assertNotFound();
    }
}