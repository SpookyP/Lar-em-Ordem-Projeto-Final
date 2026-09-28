<?php

namespace App\Http\Requests\Resident;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $residentId = $this->user()->resident?->id;
        return [
            'nif'         => ['sometimes', 'string', 'digits:9', Rule::unique('residents', 'nif')->ignore($residentId)],
            'name'        => ['sometimes', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
