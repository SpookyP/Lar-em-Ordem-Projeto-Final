<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UploadInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }

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