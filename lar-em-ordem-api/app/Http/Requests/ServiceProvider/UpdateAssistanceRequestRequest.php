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
        ];
    }

    public function messages(): array
    {
        return [
            'problem_category_id.exists' => 'A categoria indicada não existe.',
        ];
    }
}