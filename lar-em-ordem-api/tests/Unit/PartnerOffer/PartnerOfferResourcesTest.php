<?php

namespace Tests\Unit\PartnerOffer;

use App\Http\Resources\PartnerOffer\OfferResource;
use App\Http\Resources\PartnerOffer\PartnerResource;
use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * O que os resources dos parceiros e das ofertas mostram, sem base de dados.
 */
class PartnerOfferResourcesTest extends TestCase
{
    private function utilizador(string $id): User
    {
        $user = new User();
        $user->id = $id;
        $user->setRelation('roles', collect());

        return $user;
    }

    private function pedidoHttp(?User $user): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function parceiro(): Partner
    {
        $parceiro = new Partner(['company_name' => 'Parceiro Lda', 'nif' => '123456789']);
        $parceiro->user_id = 'parceiro';

        return $parceiro;
    }

    // ------------------------------------------------------- NIF do parceiro

    public function test_o_proprio_parceiro_ve_o_nif(): void
    {
        $dados = (new PartnerResource($this->parceiro()))->resolve($this->pedidoHttp($this->utilizador('parceiro')));

        $this->assertSame('123456789', $dados['nif']);
    }

    public function test_os_outros_utilizadores_nao_veem_o_nif_do_parceiro(): void
    {
        $dados = (new PartnerResource($this->parceiro()))->resolve($this->pedidoHttp($this->utilizador('morador')));

        $this->assertArrayNotHasKey('nif', $dados);
        $this->assertSame('Parceiro Lda', $dados['company_name']);
    }

    public function test_o_parceiro_nao_tem_campo_active(): void
    {
        $dados = (new PartnerResource($this->parceiro()))->resolve($this->pedidoHttp($this->utilizador('parceiro')));

        $this->assertArrayNotHasKey('active', $dados);
    }

    public function test_o_parceiro_dentro_de_uma_oferta_tambem_esconde_o_nif(): void
    {
        $oferta = new Offer(['title' => 'Desconto', 'start_date' => '2026-10-01', 'active' => true]);
        $oferta->setRelation('partner', $this->parceiro());

        // toResponse() converte também os resources dentro do resource (o partner), como a API faz
        $json = (new OfferResource($oferta))
            ->toResponse($this->pedidoHttp($this->utilizador('morador')))
            ->getData(true);

        $this->assertSame('Parceiro Lda', $json['data']['partner']['company_name']);
        $this->assertArrayNotHasKey('nif', $json['data']['partner']);
    }

    // --------------------------------------------------- formato da oferta

    public function test_a_oferta_mostra_as_datas_sem_hora_e_end_date_pode_ser_null(): void
    {
        $oferta = new Offer([
            'title'      => 'Avaliação gratuita',
            'start_date' => '2026-10-01',
            'end_date'   => null,
            'active'     => true,
        ]);

        $dados = (new OfferResource($oferta))->resolve($this->pedidoHttp($this->utilizador('morador')));

        $this->assertSame('2026-10-01', $dados['start_date']);
        $this->assertNull($dados['end_date']);
        $this->assertTrue($dados['active']);
        $this->assertArrayNotHasKey('partner', $dados);
    }
}