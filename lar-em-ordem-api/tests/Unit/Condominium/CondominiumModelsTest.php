<?php

namespace Tests\Unit\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumNotice;
use App\Models\User\User;
use Tests\TestCase;

/**
 * Métodos dos modelos do condomínio que não precisam da base de dados.
 * Os modelos são criados só em memória (new ...), nunca gravados.
 */
class CondominiumModelsTest extends TestCase
{
    private function condominio(string $adminId = 'admin-1'): Condominium
    {
        return (new Condominium(['name' => 'Edifício Brito Capelo']))->forceFill([
            'condominium_admin_id' => $adminId,
            'invite_code'          => 'CAPELA23',
            'transfer_code_hash'   => hash('sha256', 'TRANSFER2345'),
        ]);
    }

    private function utilizador(?string $adminId): User
    {
        $user = new User();

        $admin = null;
        if ($adminId) {
            $admin = new CondominiumAdmin();
            $admin->id = $adminId;
        }
        $user->setRelation('condominium_admin', $admin);

        return $user;
    }

    // ------------------------------------------------ Condominium::isManagedBy

    public function test_o_administrador_do_condominio_gere_o(): void
    {
        $this->assertTrue($this->condominio()->isManagedBy($this->utilizador('admin-1')));
    }

    public function test_outro_administrador_nao_gere_o(): void
    {
        $this->assertFalse($this->condominio()->isManagedBy($this->utilizador('admin-2')));
    }

    public function test_quem_nao_tem_perfil_de_administrador_nao_gere(): void
    {
        $this->assertFalse($this->condominio()->isManagedBy($this->utilizador(null)));
        $this->assertFalse($this->condominio()->isManagedBy(null));
    }

    // ---------------------------------------------------- Condominium (dados)

    public function test_os_codigos_nao_aparecem_quando_o_condominio_e_serializado(): void
    {
        $dados = $this->condominio()->toArray();

        $this->assertArrayNotHasKey('invite_code', $dados);
        $this->assertArrayNotHasKey('transfer_code_hash', $dados);
        $this->assertSame('Edifício Brito Capelo', $dados['name']);
    }

    public function test_o_codigo_de_convite_e_o_administrador_nao_vem_do_pedido(): void
    {
        // fora do $fillable: só o service os define (forceFill)
        $condominio = new Condominium([
            'name'                 => 'X',
            'invite_code'          => 'AAAAAAAA',
            'condominium_admin_id' => 'outro',
            'address_id'           => 'outra',
        ]);

        $this->assertNull($condominio->invite_code);
        $this->assertNull($condominio->condominium_admin_id);
        $this->assertNull($condominio->address_id);
    }

    public function test_o_fundo_de_reserva_tem_sempre_duas_casas_decimais(): void
    {
        $this->assertSame('1500.00', (new Condominium(['reserve_fund' => 1500]))->reserve_fund);
    }

    // ------------------------------------------------ CondominiumNotice::isCurrent

    public function test_um_aviso_sem_data_de_fim_esta_em_vigor(): void
    {
        $this->assertTrue((new CondominiumNotice(['expires_at' => null]))->isCurrent());
    }

    public function test_um_aviso_que_acaba_no_futuro_esta_em_vigor(): void
    {
        $this->assertTrue((new CondominiumNotice(['expires_at' => now()->addHour()]))->isCurrent());
    }

    public function test_um_aviso_que_ja_acabou_nao_esta_em_vigor(): void
    {
        $this->assertFalse((new CondominiumNotice(['expires_at' => now()->subMinute()]))->isCurrent());
    }

    public function test_o_condominio_e_a_data_de_publicacao_do_aviso_nao_vem_do_pedido(): void
    {
        $aviso = new CondominiumNotice(['title' => 'X', 'condominium_id' => 'outro', 'published_at' => '2020-01-01']);

        $this->assertNull($aviso->condominium_id);
        $this->assertNull($aviso->published_at);
    }
}