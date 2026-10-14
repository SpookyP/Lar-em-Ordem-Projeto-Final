<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UploadInvoiceRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obter as regras de validação aplicadas ao upload do ficheiro.
     */
    public function rules(): array
    {
        return [
            // Garante que é enviado um ficheiro obrigatório, do tipo PDF e com tamanho máximo de 10MB (10240 KB)
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }
}
