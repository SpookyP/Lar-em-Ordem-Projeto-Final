<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class AnnualBenchmarkSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'service' => $this->input('service', 'all'),
        ]);
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'nullable',
                'ulid',
            ],

            'service' => [
                'required',
                'in:all,1,2,3',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.ulid' => 'O identificador da habitação é inválido.',

            'service.required' => 'Indique o serviço a comparar.',
            'service.in' => 'O serviço selecionado é inválido.',
        ];
    }
}