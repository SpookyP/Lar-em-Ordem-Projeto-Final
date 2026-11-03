<?php

namespace App\Rules;

use App\Models\ServiceProvider\ServiceZone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Todos os ids da lista têm de ser zonas existentes. Uma só query para a lista inteira.
 * Valores que não são números e repetidos ficam para as regras 'integer' e 'distinct' de zone_ids.*.
 */
class ExistingServiceZones implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // ids inteiros (que chegam como texto, ex.: "101" num multipart)
        $ids = array_unique(array_filter(
            array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT), (array) $value),
            fn ($id) => $id !== false
        ));

        if (ServiceZone::whereIn('id', $ids)->count() !== count($ids)) {
            $fail('Uma das zonas indicadas não existe.');
        }
    }
}