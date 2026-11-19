<?php

namespace App\Http\Requests\Vault;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $property = $this->route('property');
        $propertyId = is_object($property) ? $property->id : $property;

        return [
            'document_category_id' => ['required', 'integer', 'exists:document_categories,id'],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('vault_documents', 'name')->where('property_id', $propertyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after:issue_date'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:15360',
                function (string $attribute, mixed $value, Closure $fail) use ($propertyId) {
                    if ($value) {
                        $hash = hash_file('sha256', $value->getRealPath());

                        $exists = DB::table('vault_documents')
                            ->where('property_id', $propertyId)
                            ->where('file_hash', $hash)
                            ->exists();

                        if ($exists) {
                            $fail('Este ficheiro exato já se encontra no seu cofre digital.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_category_id.exists' => 'A categoria de documento é inválida.',
            'name.required' => 'O nome do documento é obrigatório.',
            'name.unique' => 'Já existe um documento com este nome nesta habitação.',
            'file.mimes' => 'Apenas são aceites ficheiros em formato PDF, JPG, JPEG ou PNG.',
            'file.max' => 'O ficheiro não pode ultrapassar os 15MB.',
            'issue_date.date' => 'A data de emissão tem de ser uma data válida.',
            'issue_date.before_or_equal' => 'A data de emissão não pode ser no futuro.',
            'expiration_date.date' => 'A data de validade tem de ser uma data válida.',
            'expiration_date.after' => 'A data de validade tem de ser posterior à data de emissão.',
        ];
    }
}
