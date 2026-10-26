<?php

namespace Tests\Unit\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProviderSpecialty;
use App\Models\ServiceProvider\ServiceSpecialty;
use Tests\TestCase;

/**
 * Métodos dos modelos que não precisam da base de dados.
 * Os modelos são criados só em memória (new ...), nunca gravados.
 */
class ModelsTest extends TestCase
{
    // ------------------------------------------------- AssistanceRequest::isOpen

    public function test_um_pedido_aberto_esta_aberto(): void
    {
        $pedido = new AssistanceRequest(['status' => 'open']);

        $this->assertTrue($pedido->isOpen());
    }

    public function test_um_pedido_fechado_nao_esta_aberto(): void
    {
        $this->assertFalse((new AssistanceRequest(['status' => 'closed']))->isOpen());
    }

    public function test_um_pedido_cancelado_nao_esta_aberto(): void
    {
        $this->assertFalse((new AssistanceRequest(['status' => 'cancelled']))->isOpen());
    }

    // ---------------------------------------------- ProviderSpecialty::display_name

    public function test_display_name_usa_o_nome_proprio_quando_e_custom(): void
    {
        $especialidade = new ProviderSpecialty(['custom_name' => 'Pladur']);

        $this->assertSame('Pladur', $especialidade->display_name);
    }

    public function test_display_name_usa_o_nome_do_catalogo_quando_nao_e_custom(): void
    {
        $especialidade = new ProviderSpecialty(['custom_name' => null]);
        $especialidade->setRelation('specialty', new ServiceSpecialty(['name' => 'Fugas de água']));

        $this->assertSame('Fugas de água', $especialidade->display_name);
    }

    public function test_display_name_e_null_sem_nome_proprio_nem_especialidade(): void
    {
        $especialidade = new ProviderSpecialty(['custom_name' => null]);
        $especialidade->setRelation('specialty', null);

        $this->assertNull($especialidade->display_name);
    }
}