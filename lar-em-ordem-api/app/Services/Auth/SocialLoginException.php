<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * Falha previsivel no login social. O "reason" e um codigo fixo
 * (conta_existente, sem_email, ...) que o frontend traduz numa mensagem.
 */
class SocialLoginException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}