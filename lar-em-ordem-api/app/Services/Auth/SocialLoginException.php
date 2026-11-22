<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * Expected social sign-in failure. The reason is a fixed code (conta_existente, sem_email, ...)
 * that the frontend turns into a message.
 */
class SocialLoginException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}