<?php

namespace Tests\Unit\Condominium;

use App\Http\Requests\Condominium\ClaimCondominiumRequest;
use App\Http\Requests\Condominium\JoinCondominiumRequest;
use App\Http\Requests\Condominium\StoreCondominiumAdminRequest;
use App\Http\Requests\Condominium\StoreCondominiumDocumentRequest;
use App\Http\Requests\Condominium\StoreCondominiumNoticeRequest;
use App\Http\Requests\Condominium\StoreCondominiumRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Regras dos FormRequests do condomínio que não consultam a base de dados.
 * As regras com "exists" (zonas, casas, categorias) são testadas nos testes feature.
 */
class CondominiumValidationRulesTest extends TestCase
{
    /** Erros (campo => primeira mensagem) que as regras do FormRequest dão para $dados. */
    private function erros(string $formRequest, array $dados): array
    {
        $request = new $formRequest();

        $validator = Validator::make($dados, $request->rules(), $request->messages());

        return collect($validator->errors()->messages())->map(fn ($m) => $m[0])->all();
    }

    // ----------------------------------------------------------- condomínio

    public function test_o_condominio_exige_nome_e_morada(): void
    {
        $erros = $this->erros(StoreCondominiumRequest::class, []);

        $this->assertSame('Indique o nome do condomínio.', $erros['name']);
        $this->assertSame('Indique a morada do prédio.', $erros['address']);
    }

    public function test_o_fundo_de_reserva_nao_pode_ser_negativo(): void
    {
        $erros = $this->erros(StoreCondominiumRequest::class, ['name' => 'X', 'reserve_fund' => -1]);

        $this->assertSame('O fundo comum de reserva não pode ser negativo.', $erros['reserve_fund']);
    }

    // ------------------------------------------------------ perfil de admin

    public function test_o_perfil_de_administrador_exige_contacto_e_tipo_valido(): void
    {
        $erros = $this->erros(StoreCondominiumAdminRequest::class, ['admin_type' => 'empresa']);

        $this->assertSame('Indique um contacto (telefone ou email).', $erros['contact']);
        $this->assertSame('O tipo de administrador tem de ser "professional" (empresa) ou "resident" (condómino).', $erros['admin_type']);
    }

    public function test_o_perfil_de_administrador_valido_nao_tem_erros(): void
    {
        foreach (['professional', 'resident'] as $tipo) {
            $this->assertSame([], $this->erros(StoreCondominiumAdminRequest::class, ['contact' => '223456789', 'admin_type' => $tipo]));
        }
    }

    // --------------------------------------------------- convite e transferência

    public function test_o_codigo_de_convite_tem_8_caracteres(): void
    {
        $erros = $this->erros(JoinCondominiumRequest::class, ['invite_code' => 'CAPELA2']);

        $this->assertSame('O código de convite tem 8 caracteres.', $erros['invite_code']);
        $this->assertSame('Indique a casa.', $erros['property_id']);
    }

    public function test_o_codigo_de_transferencia_tem_12_caracteres(): void
    {
        $this->assertSame(
            'O código de transferência tem 12 caracteres.',
            $this->erros(ClaimCondominiumRequest::class, ['transfer_code' => 'ABC'])['transfer_code']
        );
        $this->assertSame([], $this->erros(ClaimCondominiumRequest::class, ['transfer_code' => 'TRANSFER2345']));
    }

    // ---------------------------------------------------------------- avisos

    public function test_um_aviso_valido_nao_tem_erros(): void
    {
        $this->assertSame([], $this->erros(StoreCondominiumNoticeRequest::class, [
            'title'      => 'Corte de água',
            'message'    => 'Terça-feira, das 9h às 13h.',
            'priority'   => 'high',
            'expires_at' => now()->addWeek()->toDateTimeString(),
        ]));

        // sem prioridade nem data de fim também é válido
        $this->assertSame([], $this->erros(StoreCondominiumNoticeRequest::class, ['title' => 'X', 'message' => 'Y']));
    }

    public function test_o_aviso_valida_prioridade_data_de_fim_e_tamanho_da_mensagem(): void
    {
        $erros = $this->erros(StoreCondominiumNoticeRequest::class, [
            'title'      => 'X',
            'message'    => str_repeat('a', 5001),
            'priority'   => 'urgente',
            'expires_at' => now()->subMinute()->toDateTimeString(),
        ]);

        $this->assertSame('A prioridade tem de ser "low", "normal" ou "high".', $erros['priority']);
        $this->assertSame('A data de fim do aviso tem de ser no futuro.', $erros['expires_at']);
        $this->assertArrayHasKey('message', $erros);
    }

    // ------------------------------------------------------------ documentos

    public function test_o_documento_aceita_pdf_jpg_e_png(): void
    {
        foreach (['ata.pdf' => 'application/pdf', 'foto.jpg' => 'image/jpeg', 'planta.png' => 'image/png'] as $nome => $mime) {
            $erros = $this->erros(StoreCondominiumDocumentRequest::class, [
                'file'          => UploadedFile::fake()->create($nome, 100, $mime),
                'title'         => 'Documento',
                'document_date' => now()->toDateString(),
            ]);

            // a categoria consulta a base de dados: é testada no CondominiumDocumentTest
            $this->assertSame([], array_diff_key($erros, ['condominium_document_category_id' => true]), $nome);
        }
    }

    public function test_o_documento_recusa_outros_tipos_ficheiros_grandes_e_datas_futuras(): void
    {
        $erros = $this->erros(StoreCondominiumDocumentRequest::class, [
            'file'          => UploadedFile::fake()->create('notas.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'document_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame('O documento tem de ser PDF, JPG ou PNG.', $erros['file']);
        $this->assertSame('A data do documento não pode ser no futuro.', $erros['document_date']);
        $this->assertSame('Indique o título do documento.', $erros['title']);
        $this->assertSame('Escolha a categoria do documento.', $erros['condominium_document_category_id']);

        $grande = $this->erros(StoreCondominiumDocumentRequest::class, ['file' => UploadedFile::fake()->create('grande.pdf', 15361, 'application/pdf')]);
        $this->assertSame('O documento não pode ter mais de 15 MB.', $grande['file']);
    }
}