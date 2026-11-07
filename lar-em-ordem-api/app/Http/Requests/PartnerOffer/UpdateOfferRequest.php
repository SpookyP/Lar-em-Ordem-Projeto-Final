<?php

namespace App\Http\Requests\PartnerOffer;


use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
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
        // Se não vier start_date no pedido, compara com o da oferta guardada
        $startDate = $this->input('start_date')
            ?? $this->route('offer')->start_date->toDateString();

        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'offer_category_id' => 'sometimes|required|integer|exists:offer_categories,id',
            'url'         => 'nullable|url|max:255',
            'start_date'  => 'sometimes|required|date',
            'end_date'    => 'nullable|date|after_or_equal:' . $startDate,
            'active'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal'    => 'A data de fim não pode ser anterior à data de início.',
            'offer_category_id.required' => 'Escolha o tipo de oferta.',
            'offer_category_id.exists'   => 'O tipo de oferta indicado não existe.',
        ];
    }
}
