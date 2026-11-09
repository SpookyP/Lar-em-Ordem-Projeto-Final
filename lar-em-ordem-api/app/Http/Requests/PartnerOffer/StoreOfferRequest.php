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
           'description' => 'required|string|max:2000',
            'offer_category_id' => 'required|integer|exists:offer_categories,id',
            'url'         => 'nullable|url|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            
        ];
        
    }
    
    public function messages(): array
    {
        return [
            'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
            'end_date.after_or_equal'    => 'A data de fim não pode ser anterior à data de início.',
            'offer_category_id.required' => 'Escolha o tipo de oferta.',
            'offer_category_id.exists'   => 'O tipo de oferta indicado não existe.',
        ];
    }
}
