<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateAssistanceRequestRequest extends FormRequest
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
            'problem_category_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('problem_categories', 'id')->whereNull('deleted_at'),
            ],
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'images'                  => 'nullable|array|max:2',
            'images.*'                => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_attachment_ids'   => 'nullable|array|max:2',
            'remove_attachment_ids.*' => 'ulid|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'problem_category_id.exists' => 'A categoria indicada não existe.',
            'images.max'                 => 'Cada pedido pode ter no máximo 2 imagens.',
            'images.*.image'             => 'O ficheiro tem de ser uma imagem.',
            'images.*.mimes'             => 'A imagem tem de ser JPG, PNG ou WEBP.',
            'images.*.max'               => 'Cada imagem pode ter no máximo 5 MB.',
        ];
    }
}