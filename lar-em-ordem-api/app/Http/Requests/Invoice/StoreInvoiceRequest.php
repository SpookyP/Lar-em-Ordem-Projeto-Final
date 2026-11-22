<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // InvoiceController authorizes with InvoicePolicy
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            // the home must have an active contract of the signed-in user;
            // any other id is reported as nonexistent
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

                    if (!$allowed) {
                        $fail('A propriedade selecionada não existe.');
                    }
                },
            ],

            // an invoice number can only be registered once per resident from the same service type (water, gas, electricity)
            'invoice_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('invoices')
                    ->where(fn ($query) => $query
                        ->where('resident_id', $this->user()->resident?->id)
                        ->where('supplier', $this->input('supplier'))
                    ),
            ],

            'issue_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'supplier' => ['required', 'string', 'max:255'],

            'file' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],

            // max main consumptions (10 per invoice)
            'consumptions' => ['required', 'array', 'max:10'],
            'consumptions.*.consumption_type_id' => [
                'required',
                'integer',
                'distinct',
                'exists:consumption_types,id',
            ],
            'consumptions.*.amount' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999.999',
            ],
            'consumptions.*.cost' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            // Sub-Items for the donut chart (30 per consumption at most)
            'consumptions.*.items' => ['nullable', 'array', 'max:30'],
            'consumptions.*.items.*.name' => ['required', 'string', 'max:255'],
            'consumptions.*.items.*.category' => [
                'nullable',
                Rule::in([
                    'consumption',
                    'fixed_fee',
                    'tax',
                    'discount',
                    'other',
                ]),
            ],
            'consumptions.*.items.*.amount' => [
                'nullable',
                'numeric',
                'between:-99999999.99,99999999.99',
            ],
            'consumptions.*.items.*.unit' => ['nullable', 'string', 'max:50'],
            'consumptions.*.items.*.cost' => [
                'required',
                'numeric',
                'between:-99999999.99,99999999.99',
            ], // negatives allowed (e.g. solar self-consumption)
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
            'invoice_number.unique' => 'Este número de fatura já foi registado para este fornecedor.',
            'period_end.after_or_equal' => 'A data de fim do período deve ser igual ou posterior à data de início.',
            'total_amount.min' => 'O valor total não pode ser negativo.',

            'file.file' => 'O ficheiro enviado é inválido.',
            'file.mimes' => 'A fatura tem de ser um ficheiro PDF.',
            'file.mimetypes' => 'A fatura tem de ser um ficheiro PDF.',
            'file.max' => 'O ficheiro não pode ter mais de 10 MB.',
            'file.uploaded' => 'Não foi possível carregar o ficheiro. Tente novamente.',

            'consumptions.required' => 'Indique pelo menos um consumo.',
            'consumptions.max' => 'Cada fatura pode ter no máximo 10 consumos.',

            'consumptions.*.consumption_type_id.exists' => 'O tipo de consumo selecionado não existe.',
            'consumptions.*.consumption_type_id.distinct' => 'Cada tipo de consumo só pode aparecer uma vez por fatura.',
            'consumptions.*.items.max' => 'Cada consumo pode ter no máximo 30 itens.',
            'consumptions.*.items.*.category.in' => 'A categoria do item é inválida.',
        ];
    }

    /** Readable field names used in the validation messages. */
    public function attributes(): array
    {
        return [
            'property_id' => 'habitação',
            'invoice_number' => 'número da fatura',
            'issue_date' => 'data de emissão',
            'period_start' => 'início do período',
            'period_end' => 'fim do período',
            'total_amount' => 'valor total',
            'supplier' => 'fornecedor',
            'file' => 'ficheiro',

            'consumptions' => 'consumos',
            'consumptions.*.consumption_type_id' => 'tipo de consumo',
            'consumptions.*.amount' => 'quantidade',
            'consumptions.*.cost' => 'custo',
            'consumptions.*.items' => 'itens',
            'consumptions.*.items.*.name' => 'descrição do item',
            'consumptions.*.items.*.category' => 'categoria do item',
            'consumptions.*.items.*.amount' => 'quantidade do item',
            'consumptions.*.items.*.unit' => 'unidade do item',
            'consumptions.*.items.*.cost' => 'custo do item',
        ];
    }
}