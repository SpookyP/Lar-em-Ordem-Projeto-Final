<?php

namespace App\Http\Requests\PartnerOffer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
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
        return [
            'name'        => 'sometimes|required|string|max:255',
            'nif'         => 'sometimes|required|string|digits:9',
            'phone'       => 'sometimes|required|string|max:15',
            'website'     => 'nullable|string|url|max:255',
            'description' => 'sometimes|required|string',
            'active'      => 'sometimes|required|boolean'
        ];
    }
}
