<?php

namespace Tests\Unit\Condominium;

use App\Http\Resources\Condominium\CondominiumDocumentResource;
use App\Http\Resources\Condominium\CondominiumNoticeResource;
use App\Http\Resources\Condominium\CondominiumResource;
use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Models\Condominium\CondominiumNotice;
use App\Models\File\File;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * O que os resources do condomínio mostram a cada utilizador, sem base de dados.
 * O código de convite, as frações e a transferência só aparecem a quem gere o condomínio.
 */
class CondominiumResourcesTest extends TestCase
{
    /** Utilizador em memória; $adminId = id do perfil de administrador (null = não é administrador). */
    private function utilizador(?string $adminId = null, array $roles = []): User
    {
        $user = new User();
        $user->id = 'user-' . ($adminId ?? 'residente');
        $user->setRelation('roles', collect(array_map(fn ($nome) => new Role(['name' => $nome, 'guard_name' => 'web']), $roles)));

        $admin = null;
        if ($adminId) {
            $admin = new CondominiumAdmin();
            $admin->id = $adminId;
        }
        $user->setRelation('condominium_admin', $admin);

        return $user;
    }

    private function pedidoHttp(?User $user): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function condominio(): Condominium
    {
        $condominio = new Condominium(['name' => 'Edifício Brito Capelo', 'reserve_fund' => 12500]);
        $condominio->forceFill([
            'id'                   => 'condominio',
            'condominium_admin_id' => 'admin-1',
            'invite_code'          => 'CAPELA23',
            'transfer_expires_at'  => '2026-12-01 10:00:00',
        ]);

        return $condominio;
    }

    private function dados(Condominium $condominio, ?User $user): array
    {
        return (new CondominiumResource($condominio))->resolve($this->pedidoHttp($user));
    }

    // ------------------------------------------------------- dados de gestão

    public function test_o_administrador_ve_o_codigo_de_convite_e_a_transferencia(): void
    {
        $dados = $this->dados($this->condominio(), $this->utilizador('admin-1'));

        $this->assertTrue($dados['can_manage']);
        $this->assertSame('CAPELA23', $dados['invite_code']);
        $this->assertNotNull($dados['transfer_expires_at']);
    }

    public function test_o_residente_nao_ve_o_codigo_nem_a_transferencia(): void
    {
        $dados = $this->dados($this->condominio(), $this->utilizador());

        $this->assertFalse($dados['can_manage']);
        $this->assertArrayNotHasKey('invite_code', $dados);
        $this->assertArrayNotHasKey('transfer_expires_at', $dados);
        $this->assertArrayNotHasKey('properties_count', $dados);
        $this->assertSame('Edifício Brito Capelo', $dados['name']);
    }

    public function test_outro_administrador_nao_ve_o_codigo(): void
    {
        $dados = $this->dados($this->condominio(), $this->utilizador('admin-2'));

        $this->assertFalse($dados['can_manage']);
        $this->assertArrayNotHasKey('invite_code', $dados);
    }

    public function test_sem_sessao_nao_ha_dados_de_gestao(): void
    {
        $dados = $this->dados($this->condominio(), null);

        $this->assertFalse($dados['can_manage']);
        $this->assertArrayNotHasKey('invite_code', $dados);
    }

    // ------------------------------------------------------------- frações

    public function test_as_fracoes_so_aparecem_a_quem_gere_e_se_foram_carregadas(): void
    {
        $morada = new Address(['door' => '2º Esq', 'fraction' => 'B']);
        $casa = new Property();
        $casa->id = 'casa-1';
        $casa->setRelation('address', $morada);

        $condominio = $this->condominio();

        // sem as casas carregadas: nem o administrador as vê
        $this->assertArrayNotHasKey('properties', $this->dados($condominio, $this->utilizador('admin-1')));

        $condominio->setRelation('properties', collect([$casa]));

        $fracoes = $this->dados($condominio, $this->utilizador('admin-1'))['properties'];
        $this->assertSame([['id' => 'casa-1', 'door' => '2º Esq', 'fraction' => 'B']], $fracoes->all());

        // o residente nunca as vê
        $this->assertArrayNotHasKey('properties', $this->dados($condominio, $this->utilizador()));
    }

    public function test_morada_e_administrador_so_aparecem_se_foram_carregados(): void
    {
        $condominio = $this->condominio();
        $dados = $this->dados($condominio, $this->utilizador());

        $this->assertArrayNotHasKey('address', $dados);
        $this->assertArrayNotHasKey('admin', $dados);

        $admin = new CondominiumAdmin(['contact' => '223456789']);
        $admin->setRelation('user', new User(['name' => 'administrador']));
        $condominio->setRelation('admin', $admin);
        $condominio->setRelation('address', new Address(['street' => 'Rua Brito Capelo', 'county' => 'Matosinhos', 'district' => 'Porto']));

        $dados = $this->dados($condominio, $this->utilizador());
        $this->assertSame(['name' => 'administrador', 'contact' => '223456789'], $dados['admin']);
        $this->assertSame('Matosinhos', $dados['address']['county']);
    }

    // ---------------------------------------------------------------- avisos

    public function test_o_aviso_so_mostra_read_e_readers_count_quando_existem(): void
    {
        $aviso = new CondominiumNotice(['title' => 'Corte de água', 'message' => 'Terça-feira.', 'priority' => 'high']);

        $dados = (new CondominiumNoticeResource($aviso))->resolve($this->pedidoHttp(null));
        $this->assertArrayNotHasKey('read', $dados);
        $this->assertArrayNotHasKey('readers_count', $dados);

        // sino do residente (withExists) e administrador (withCount)
        $aviso->setAttribute('read', 1);
        $aviso->setAttribute('readers_count', 3);

        $dados = (new CondominiumNoticeResource($aviso))->resolve($this->pedidoHttp(null));
        $this->assertTrue($dados['read']);
        $this->assertSame(3, $dados['readers_count']);
    }

    // ------------------------------------------------------------ documentos

    public function test_o_documento_mostra_a_data_sem_hora_e_o_ficheiro_sem_link(): void
    {
        $documento = new CondominiumDocument(['title' => 'Ata de março', 'document_date' => '2026-03-15']);
        $documento->setRelation('category', new CondominiumDocumentCategory(['name' => 'Atas de assembleia']));
        $documento->setRelation('file', new File([
            'original_name' => 'ata.pdf',
            'mime_type'     => 'application/pdf',
            'size_in_bytes' => 2048,
            'path'          => 'users/x/condominium_documents/ata.pdf',
        ]));

        $dados = (new CondominiumDocumentResource($documento))->resolve($this->pedidoHttp(null));

        $this->assertSame('2026-03-15', $dados['document_date']);
        $this->assertSame('Atas de assembleia', $dados['category']['name']);
        $this->assertSame(['name' => 'ata.pdf', 'mime_type' => 'application/pdf', 'size' => 2048], $dados['file']);
    }

    public function test_o_documento_sem_relacoes_carregadas_nao_as_mostra(): void
    {
        $documento = new CondominiumDocument(['title' => 'Regulamento', 'document_date' => '2025-01-10']);

        $dados = (new CondominiumDocumentResource($documento))->resolve($this->pedidoHttp(null));

        $this->assertArrayNotHasKey('category', $dados);
        $this->assertArrayNotHasKey('file', $dados);
    }
}