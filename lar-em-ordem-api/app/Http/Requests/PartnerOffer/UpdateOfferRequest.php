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
            'type'        => 'sometimes|required|string|max:100',
            'url'         => 'nullable|url|max:255',
            'start_date'  => 'sometimes|required|date',
            'end_date'    => 'nullable|date|after_or_equal:' . $startDate,
            'active'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
        ];
    }
}
