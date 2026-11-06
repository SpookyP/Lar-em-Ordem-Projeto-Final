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

    /**
     * Mensagens de erro em português.
     */
    public function messages(): array
    {
        return [
            'file.required'  => 'Selecione um ficheiro PDF.',
            'file.file'      => 'O ficheiro enviado é inválido.',
            'file.mimes'     => 'A fatura tem de ser um ficheiro PDF.',
            'file.mimetypes' => 'A fatura tem de ser um ficheiro PDF.',
            'file.max'       => 'O ficheiro não pode ter mais de 10 MB.',
            'file.uploaded'  => 'Não foi possível carregar o ficheiro. Tente novamente.',
        ];
    }
}