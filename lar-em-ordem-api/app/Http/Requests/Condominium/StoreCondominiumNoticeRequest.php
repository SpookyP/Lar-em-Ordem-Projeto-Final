<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCondominiumNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string', 'max:5000'],
            'priority'   => ['sometimes', 'string', Rule::in(['low', 'normal', 'high'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Indique o título do aviso.',
            'message.required' => 'Escreva a mensagem do aviso.',
            'priority.in'      => 'A prioridade tem de ser "low", "normal" ou "high".',
            'expires_at.after' => 'A data de fim do aviso tem de ser no futuro.',
        ];
    }
}