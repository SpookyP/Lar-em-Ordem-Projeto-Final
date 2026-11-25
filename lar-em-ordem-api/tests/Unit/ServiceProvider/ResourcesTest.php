<?php

namespace Tests\Unit\ServiceProvider;

use App\Http\Resources\ServiceProvider\AssistanceRequestResource;
use App\Http\Resources\ServiceProvider\ProposalResource;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use App\Models\User\Resident;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * What the resources show each user (address, NIF, time format).
 * The models and relations are built in memory: no test uses the database.
 */
class ResourcesTest extends TestCase
{
    // ---------------------------------------------------------------- helpers

    /**
     * In-memory user with no roles (hasRole doesn't need the database),
     * with resident profile "res-{id}".
     */
    private function utilizador(string $id): User
    {
        $user = new User();
        $user->id = $id;
        $user->setRelation('roles', collect());

        $residente = new Resident();
        $residente->id = "res-{$id}";
        $user->setRelation('resident', $residente);

        return $user;
    }

    /** HTTP request made by $user, as it reaches the resource. */
    private function pedidoHttp(?User $user): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    /** Assistance request owned by "dono", on a property in Rua Brito Capelo, with the proposal from $aceiteDe accepted. */
    private function pedidoAssistencia(?string $aceiteDe = null): AssistanceRequest
    {
        $morada = new Address([
            'street'      => 'Rua Brito Capelo',
            'postal_code' => '4450-073',
            'fraction'    => '2º Esq',
            'location' => 'Matosinhos',
            'county'   => 'Matosinhos',
            'district' => 'Porto',
        ]);

        $casa = new Property();
        $casa->id = 'casa-1';
        $casa->setRelation('address', $morada);

        $pedido = new AssistanceRequest(['title' => 'Tomada', 'status' => 'open']);
        $pedido->id = 'pedido-1';
        $pedido->resident_id = 'res-dono';
        $pedido->setRelation('property', $casa);

        $aceite = null;
        if ($aceiteDe) {
            $prestador = new ServiceProviderModel(['company_name' => 'Serviços Lda']);
            $prestador->user_id = $aceiteDe;

            $aceite = new Proposal(['status' => 'accepted']);
            $aceite->setRelation('serviceProvider', $prestador);
        }
        $pedido->setRelation('acceptedProposal', $aceite);

        return $pedido;
    }

    private function propriedade(AssistanceRequest $pedido, ?User $quemVe): array
    {
        $dados = (new AssistanceRequestResource($pedido))->resolve($this->pedidoHttp($quemVe));

        return $dados['property'];
    }

    // ------------------------------------------------- request address

    public function test_o_dono_ve_a_morada_completa_e_o_id_da_casa(): void
    {
        $casa = $this->propriedade($this->pedidoAssistencia(), $this->utilizador('dono'));

        $this->assertSame('casa-1', $casa['id']);
        $this->assertSame('Rua Brito Capelo', $casa['street']);
        $this->assertSame('4450-073', $casa['postal_code']);
        $this->assertArrayNotHasKey('fraction', $casa);
    }

    public function test_um_prestador_qualquer_so_ve_a_localidade(): void
    {
        $casa = $this->propriedade($this->pedidoAssistencia(), $this->utilizador('prestador'));

        $this->assertNull($casa['id']);
        $this->assertNull($casa['street']);
        $this->assertNull($casa['postal_code']);
        $this->assertSame('Matosinhos', $casa['county']);
        $this->assertSame('Porto', $casa['district']);
    }

    public function test_o_prestador_com_a_proposta_aceite_ve_a_morada(): void
    {
        $casa = $this->propriedade($this->pedidoAssistencia(aceiteDe: 'prestador'), $this->utilizador('prestador'));

        $this->assertSame('casa-1', $casa['id']);
        $this->assertSame('Rua Brito Capelo', $casa['street']);
    }

    public function test_outro_prestador_nao_ve_a_morada_mesmo_com_proposta_aceite_de_outro(): void
    {
        $casa = $this->propriedade($this->pedidoAssistencia(aceiteDe: 'prestador'), $this->utilizador('outro'));

        $this->assertNull($casa['street']);
    }

    public function test_relacoes_nao_carregadas_nao_aparecem_na_resposta(): void
    {
        $pedido = new AssistanceRequest(['title' => 'Tomada', 'status' => 'open']);
        $pedido->resident_id = 'res-dono';

        $dados = (new AssistanceRequestResource($pedido))->resolve($this->pedidoHttp($this->utilizador('dono')));

        $this->assertArrayNotHasKey('property', $dados);
        $this->assertArrayNotHasKey('proposals', $dados);
        $this->assertArrayNotHasKey('pending_proposals_count', $dados);
    }

    // ---------------------------------------------------- provider NIF

    public function test_o_proprio_prestador_ve_o_nif(): void
    {
        $prestador = new ServiceProviderModel(['company_name' => 'Serviços Lda', 'nif' => '212345673']);
        $prestador->user_id = 'prestador';

        $dados = (new ServiceProviderResource($prestador))->resolve($this->pedidoHttp($this->utilizador('prestador')));

        $this->assertSame('212345673', $dados['nif']);
    }

    public function test_os_outros_utilizadores_nao_veem_o_nif_do_prestador(): void
    {
        $prestador = new ServiceProviderModel(['company_name' => 'Serviços Lda', 'nif' => '212345673']);
        $prestador->user_id = 'prestador';

        $dados = (new ServiceProviderResource($prestador))->resolve($this->pedidoHttp($this->utilizador('morador')));

        $this->assertArrayNotHasKey('nif', $dados);
        $this->assertSame('Serviços Lda', $dados['company_name']);
    }

    public function test_sem_sessao_o_nif_do_prestador_nao_aparece(): void
    {
        $prestador = new ServiceProviderModel(['nif' => '212345673']);
        $prestador->user_id = 'prestador';

        $dados = (new ServiceProviderResource($prestador))->resolve($this->pedidoHttp(null));

        $this->assertArrayNotHasKey('nif', $dados);
    }

    // ------------------------------------------------- proposal format

    public function test_a_proposta_mostra_as_horas_em_hh_mm_e_as_datas_sem_hora(): void
    {
        $proposta = new Proposal([
            'estimated_value' => 45,
            'validity'        => '2026-11-30',
            'available_date'  => '2026-11-05',
            'available_from'  => '09:00:00',
            'available_until' => '12:30:00',
            'status'          => 'pending',
        ]);

        $dados = (new ProposalResource($proposta))->resolve($this->pedidoHttp($this->utilizador('dono')));

        $this->assertSame('09:00', $dados['available_from']);
        $this->assertSame('12:30', $dados['available_until']);
        $this->assertSame('2026-11-30', $dados['validity']);
        $this->assertSame('2026-11-05', $dados['available_date']);
        $this->assertSame('45.00', $dados['estimated_value']);
    }
}