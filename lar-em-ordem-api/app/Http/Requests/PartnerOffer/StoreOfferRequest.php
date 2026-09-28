<?php

namespace App\Http\Requests\PartnerOffer;


use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
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

            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'type'        => 'required|string|max:100',
            'url'         => 'nullable|string|max:500',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            
        ];
        
    }
    
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
        ];
    }
}
