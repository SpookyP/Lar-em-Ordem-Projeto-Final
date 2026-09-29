<?php

namespace App\Http\Requests\Contract;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->resident !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resident_type_id' => ['sometimes','nullable', 'integer', 'exists:resident_types,id'],
            'start_date'       => ['sometimes','nullable', 'date'],
            'end_date'         => ['sometimes','nullable', 'date', 'after_or_equal:start_date'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
