<?php

namespace Tests\Unit\ServiceProvider;

use App\Http\Requests\PartnerOffer\StoreOfferRequest;
use App\Http\Requests\ServiceProvider\PerPageRequest;
use App\Http\Requests\ServiceProvider\StoreProposalRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Regras dos FormRequests que não consultam a base de dados (sem "exists").
 * Valida os dados diretamente com as regras e mensagens de cada FormRequest.
 */
class ValidationRulesTest extends TestCase
{
    /** Erros (campo => primeira mensagem) que as regras do FormRequest dão para $dados. */
    private function erros(string $formRequest, array $dados): array
    {
        $request = new $formRequest();

        $validator = Validator::make($dados, $request->rules(), $request->messages());

        return collect($validator->errors()->messages())->map(fn ($m) => $m[0])->all();
    }

    private function propostaValida(array $extra = []): array
    {
        return $extra + [
            'estimated_value' => 80,
            'description'     => 'Substituição do sifão.',
            'validity'        => now()->addDays(10)->toDateString(),
            'available_date'  => now()->addDays(2)->toDateString(),
            'available_from'  => '09:00',
            'available_until' => '12:00',
        ];
    }

    // ------------------------------------------------------------- proposta

    public function test_uma_proposta_valida_nao_tem_erros(): void
    {
        $this->assertSame([], $this->erros(StoreProposalRequest::class, $this->propostaValida()));
    }

    public function test_a_proposta_exige_todos_os_campos(): void
    {
        $erros = $this->erros(StoreProposalRequest::class, []);

        $this->assertEqualsCanonicalizing(
            ['estimated_value', 'description', 'validity', 'available_date', 'available_from', 'available_until'],
            array_keys($erros)
        );
    }

    public function test_o_valor_tem_de_ser_positivo(): void
    {
        $erros = $this->erros(StoreProposalRequest::class, $this->propostaValida(['estimated_value' => 0]));

        $this->assertArrayHasKey('estimated_value', $erros);
    }

    public function test_as_datas_nao_podem_ser_passadas(): void
    {
        $ontem = now()->subDay()->toDateString();

        $erros = $this->erros(StoreProposalRequest::class, $this->propostaValida([
            'validity'       => $ontem,
            'available_date' => $ontem,
        ]));

        $this->assertSame('A validade da proposta não pode ser uma data passada.', $erros['validity']);
        $this->assertSame('A data de disponibilidade não pode ser uma data passada.', $erros['available_date']);
    }

    public function test_hoje_e_uma_data_valida(): void
    {
        $hoje = now()->toDateString();

        $erros = $this->erros(StoreProposalRequest::class, $this->propostaValida([
            'validity'       => $hoje,
            'available_date' => $hoje,
        ]));

        $this->assertSame([], $erros);
    }

    public function test_as_horas_tem_de_estar_no_formato_hh_mm(): void
    {
        $erros = $this->erros(StoreProposalRequest::class, $this->propostaValida([
            'available_from'  => '9h',
            'available_until' => '15h',
        ]));

        $this->assertSame('A hora de início tem de estar no formato HH:MM (ex: 10:00).', $erros['available_from']);
        $this->assertSame('A hora de fim tem de estar no formato HH:MM (ex: 15:00).', $erros['available_until']);
    }

    public function test_a_hora_de_fim_tem_de_ser_depois_da_de_inicio(): void
    {
        foreach (['10:00', '08:00'] as $fim) {
            $erros = $this->erros(StoreProposalRequest::class, $this->propostaValida([
                'available_from'  => '10:00',
                'available_until' => $fim,
            ]));

            $this->assertSame('A hora de fim tem de ser depois da hora de início.', $erros['available_until']);
        }
    }

    // ----------------------------------------------------------- paginação

    public function test_per_page_e_opcional_e_aceita_de_1_a_15(): void
    {
        $this->assertSame([], $this->erros(PerPageRequest::class, []));
        $this->assertSame([], $this->erros(PerPageRequest::class, ['per_page' => 1]));
        $this->assertSame([], $this->erros(PerPageRequest::class, ['per_page' => 15]));
    }

    public function test_per_page_fora_dos_limites_da_erro(): void
    {
        foreach ([0, 16, 'abc'] as $valor) {
            $erros = $this->erros(PerPageRequest::class, ['per_page' => $valor]);

            $this->assertArrayHasKey('per_page', $erros, "per_page={$valor} devia dar erro");
        }

        $this->assertSame(
            'O número de resultados por página tem de estar entre 1 e 15.',
            $this->erros(PerPageRequest::class, ['per_page' => 16])['per_page']
        );
    }

    // --------------------------------------------------------------- oferta

    public function test_uma_oferta_sem_data_de_fim_e_valida(): void
    {
        $erros = $this->erros(StoreOfferRequest::class, [
            'title'       => 'Avaliação gratuita',
            'description' => 'Sem compromisso.',
            'type'        => 'Serviço',
            'start_date'  => '2026-10-01',
        ]);

        $this->assertSame([], $erros);
    }

    public function test_a_data_de_fim_da_oferta_nao_pode_ser_anterior_a_de_inicio(): void
    {
        $erros = $this->erros(StoreOfferRequest::class, [
            'title'       => 'Desconto',
            'description' => 'x',
            'type'        => 'Desconto',
            'start_date'  => '2026-10-10',
            'end_date'    => '2026-10-01',
        ]);

        $this->assertSame('A data de fim não pode ser anterior à data de início.', $erros['end_date']);
    }

    public function test_o_url_da_oferta_tem_de_ser_valido(): void
    {
        $erros = $this->erros(StoreOfferRequest::class, [
            'title'       => 'Desconto',
            'description' => 'x',
            'type'        => 'Desconto',
            'start_date'  => '2026-10-01',
            'url'         => 'não é um url',
        ]);

        $this->assertArrayHasKey('url', $erros);
    }
}