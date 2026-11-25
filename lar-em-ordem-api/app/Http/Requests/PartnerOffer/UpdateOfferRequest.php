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
        // If the request has no start_date, compare with the stored offer's
        $startDate = $this->input('start_date')
            ?? $this->route('offer')->start_date->toDateString();
        
        // If the request has no end_date, compare with the stored offer's (no end means no limit)
        $endDate = $this->exists('end_date')
            ? $this->input('end_date')
            : $this->route('offer')->end_date?->toDateString();

        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:2000',
            'offer_category_id' => 'sometimes|required|integer|exists:offer_categories,id',
            'url'         => 'nullable|url|max:255',
            'start_date'  => 'sometimes|required|date' . ($endDate ? '|before_or_equal:' . $endDate : ''),
            'end_date'    => 'nullable|date|after_or_equal:' . $startDate,
            'active'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
             'start_date.before_or_equal' => 'A data de início não pode ser depois da data de fim.',
            'end_date.after_or_equal'    => 'A data de fim não pode ser anterior à data de início.',
            'offer_category_id.required' => 'Escolha o tipo de oferta.',
            'offer_category_id.exists'   => 'O tipo de oferta indicado não existe.',
        ];
    }
}
