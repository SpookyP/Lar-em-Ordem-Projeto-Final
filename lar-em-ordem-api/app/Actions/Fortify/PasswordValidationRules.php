<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
            'confirmed'
        ];
    }

    /**
     * Mensagens em português para as regras acima (senão vinham as do Laravel, em inglês).
     *
     * @return array<string, string>
     */
    protected function passwordMessages(): array
    {
        return [
            'password.required' => 'Indique a password.',
            'password.min' => 'A password tem de ter pelo menos 12 caracteres.',
            'password.letters' => 'A password tem de ter pelo menos uma letra.',
            'password.mixed' => 'A password tem de ter pelo menos uma letra minúscula e uma maiúscula.',
            'password.numbers' => 'A password tem de ter pelo menos um número.',
            'password.symbols' => 'A password tem de ter pelo menos um símbolo.',
            'password.uncompromised' => 'Esta password apareceu numa fuga de dados. Por segurança, escolha outra.',
            'password.confirmed' => 'As passwords não coincidem.',
        ];
    }
}