<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Middleware handled
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $allowed = \App\Models\Property\Property::query()
                        ->whereKey($value)
                        ->whereHas('residents', fn ($q) => $q
                            ->where('residents.user_id', $this->user()->id)
                            ->where('property_contracts.is_active', true))
                        ->exists();

                    if (! $allowed) {
                        $fail('A propriedade selecionada não existe.');
                    }
                },
            ],

            'invoice_number' => ['required', 'string', 'max:100',
                Rule::unique('invoices')
                ->where(fn ($query) => $query
                    ->where('resident_id', $this->user()->resident?->id)
                    ->where('supplier', $this->input('supplier'))
                )
            ],

            'issue_date'                         =>      ['required', 'date'],
            'period_start'                       =>      ['required', 'date'],
            'period_end'                         =>      ['required', 'date', 'after_or_equal:period_start'],
            'total_amount'                       =>      ['required', 'numeric', 'min:0'],
            'supplier'                           =>      ['required', 'string', 'max:255'],

            'file_id' => ['bail', 'nullable', 'ulid', function ($attribute, $value, $fail) {
                if (! Storage::disk('r2')->exists("pending/{$this->user()->id}/{$value}.pdf")) {
                    $fail('O ficheiro da fatura é inválido ou já expirou.');
                }
            }],

            // Consumos principais (no máximo 10 por fatura)
            'consumptions'                          =>      ['required', 'array', 'max:10'],
            'consumptions.*.consumption_type_id'    =>      ['required', 'integer', 'exists:consumption_types,id'],
            'consumptions.*.amount'                 =>      ['required', 'numeric', 'min:0', 'max:9999999.999'],
            'consumptions.*.cost'                   =>      ['required', 'numeric', 'min:0', 'max:99999999.99'],

            // Sub-itens detalhados opcionais para o gráfico de anel (no máximo 30 por consumo)
            'consumptions.*.items'                  =>      ['nullable', 'array', 'max:30'],
            'consumptions.*.items.*.name'           =>      ['required', 'string', 'max:255'],
            'consumptions.*.items.*.category'       =>      ['nullable', Rule::in(['consumption', 'fixed_fee', 'tax', 'discount', 'other'])],
            'consumptions.*.items.*.amount'         =>      ['nullable', 'numeric', 'between:-99999999.99,99999999.99'],
            'consumptions.*.items.*.unit'           =>      ['nullable', 'string', 'max:50'],
            'consumptions.*.items.*.cost'           =>      ['required', 'numeric', 'between:-99999999.99,99999999.99'], // negativos permitidos (ex: autoconsumo solar)
        ];
    }

    /**
     * Extract only Invoice data.
     */
    public function invoiceData(): array
    {
        return $this->safe()->only([
            'property_id',
            'invoice_number',
            'issue_date',
            'period_start',
            'period_end',
            'total_amount',
            'supplier',
            'file_id',
        ]);
    }

    /**
     * Extract consumptions with their items data.
     */
    public function consumptionsData(): array
    {
        return $this->validated('consumptions', []);
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'invoice_number.unique'     => 'Este número de fatura já foi registado para este fornecedor.',
            'period_end.after_or_equal' => 'A data de fim do período deve ser igual ou posterior à data de início.',
            'total_amount.min'          => 'O valor total não pode ser negativo.',
            'file_id.ulid'              => 'O identificador do ficheiro é inválido.',

            'consumptions.required' => 'Indique pelo menos um consumo.',
            'consumptions.max'      => 'Cada fatura pode ter no máximo 10 consumos.',

            'consumptions.*.consumption_type_id.exists' => 'O tipo de consumo selecionado não existe.',
            'consumptions.*.items.max'                  => 'Cada consumo pode ter no máximo 30 itens.',
            'consumptions.*.items.*.category.in'        => 'A categoria do item é inválida.',
        ];
    }

    /**
     * Nomes legíveis dos campos nas mensagens de validação.
     */
    public function attributes(): array
    {
        return [
            'property_id'    => 'habitação',
            'invoice_number' => 'número da fatura',
            'issue_date'     => 'data de emissão',
            'period_start'   => 'início do período',
            'period_end'     => 'fim do período',
            'total_amount'   => 'valor total',
            'supplier'       => 'fornecedor',
            'file_id'        => 'ficheiro',

            'consumptions'                       => 'consumos',
            'consumptions.*.consumption_type_id' => 'tipo de consumo',
            'consumptions.*.amount'              => 'quantidade',
            'consumptions.*.cost'                => 'custo',
            'consumptions.*.items'               => 'itens',
            'consumptions.*.items.*.name'        => 'descrição do item',
            'consumptions.*.items.*.category'    => 'categoria do item',
            'consumptions.*.items.*.amount'      => 'quantidade do item',
            'consumptions.*.items.*.unit'        => 'unidade do item',
            'consumptions.*.items.*.cost'        => 'custo do item',
        ];
    }
}