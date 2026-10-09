<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class IndexServiceZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtros opcionais da listagem: ?district=Porto&county=Matosinhos
     */
    public function rules(): array
    {
        return [
            'district' => 'sometimes|string|max:255',
            'county'   => 'sometimes|string|max:255',
        ];
    }
}