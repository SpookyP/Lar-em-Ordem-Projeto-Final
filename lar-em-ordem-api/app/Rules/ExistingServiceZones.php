<?php

namespace App\Rules;

use App\Models\ServiceProvider\ServiceZone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every id in the list must be an existing zone, checked with a single query for the whole list.
 * Non-numeric and repeated values are left to the 'integer' and 'distinct' rules on zone_ids.*.
 */
class ExistingServiceZones implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // integer ids, which arrive as text in a multipart request (e.g. "101")
        $ids = array_unique(array_filter(
            array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT), (array) $value),
            fn ($id) => $id !== false
        ));

        if (ServiceZone::whereIn('id', $ids)->count() !== count($ids)) {
            $fail('Uma das zonas indicadas não existe.');
        }
    }
}