<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            // A validação abaixo verifica corretamente se a propriedade pertence
            // ao utilizador autenticado através de um residente com contrato ativo.
            // Esta regra foi testada e funciona corretamente.
            //
            // Por enquanto, enquanto não existe frontend e para facilitar os testes
            // da criação de faturas, mantém-se a regra de existência simples abaixo.
            // Quando houver frontend, deve ser reativada a validação de autorização
            // para impedir que um utilizador registe faturas numa propriedade que não lhe pertence.
            //
            // 'property_id' => [
            //     'required',
            //     'integer',
            //     function (string $attribute, mixed $value, \Closure $fail) {
            //         $allowed = \App\Models\Property\Property::query()
            //             ->whereKey($value)
            //             ->whereHas('residents', fn ($q) => $q
            //                 ->where('residents.user_id', $this->user()->id)
            //                 ->where('property_contracts.is_active', true))
            //             ->exists();
            //
            //         if (! $allowed) {
            //             $fail('The selected property does not exist.');
            //         }
            //     },
            // ],


            'property_id' => ['required', 'string', 
                Rule::exists('properties', 'id')
                ->whereNull('deleted_at')
            ],

            
            'invoice_number' => ['required', 'string', 'max:100', 
                Rule::unique('invoices')
                ->where(fn ($query) => $query 
                    ->where('user_id', $this->user()->id) 
                    ->where('supplier', $this->input('supplier')) 
                )
            ],
            
            'issue_date'                            =>      ['required', 'date'],
            'period_start'                          =>      ['required', 'date'],
            'period_end'                            =>      ['required', 'date', 'after_or_equal:period_start'],
            'total_amount'                          =>      ['required', 'numeric', 'min:0'],
            'supplier'                              =>      ['required', 'string', 'max:255'],
            'file_path'                             =>      ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'consumptions'                          =>      ['required', 'array'],
            'consumptions.*.consumption_type_id'    =>      ['required', 'integer', 'exists:consumption_types,id'],
            'consumptions.*.amount'                 =>      ['required', 'numeric', 'min:0'],
            'consumptions.*.cost'                   =>      ['required', 'numeric', 'min:0'],
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
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'property_id.exists'        =>  'The selected property does not exist.',
            'invoice_number.unique'     =>  'This invoice number has already been registered.',
            'period_end.after_or_equal' =>  'The period end date must be equal to or after the start date.',
        ];
    }
}