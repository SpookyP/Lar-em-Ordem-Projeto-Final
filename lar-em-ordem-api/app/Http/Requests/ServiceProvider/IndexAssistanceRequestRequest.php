<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssistanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtro opcional da listagem: ?status=open
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:open,in_progress,closed,cancelled',
        ];
    }
}