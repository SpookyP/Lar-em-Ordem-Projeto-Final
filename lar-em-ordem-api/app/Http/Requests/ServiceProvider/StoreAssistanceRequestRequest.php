<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistanceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'required', 'ulid',
                Rule::exists('properties', 'id')->whereNull('deleted_at'),
            ],
            'problem_category_id' => [
                'required', 'integer',
                Rule::exists('problem_categories', 'id')->whereNull('deleted_at'),
            ],
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'images'      => 'nullable|array|max:2',
            'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.exists'         => 'A casa indicada não existe.',
            'problem_category_id.exists' => 'A categoria indicada não existe.',
            'images.max'                 => 'Cada pedido pode ter no máximo 2 imagens.',
            'images.*.image'             => 'O ficheiro tem de ser uma imagem.',
            'images.*.mimes'             => 'A imagem tem de ser JPG, PNG ou WEBP.',
            'images.*.max'               => 'Cada imagem pode ter no máximo 5 MB.',
        ];
    }
}
