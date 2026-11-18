<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email with a button that links to the frontend to choose a new password ("Esqueci-me da password").
 */
class ResetPassword extends Notification
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly int $minutes,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Repor a password - Lar em Ordem')
            ->greeting('Olá, ' . $this->name . '!')
            ->line('Recebemos um pedido para repor a password da sua conta. Clique no botão abaixo para escolher uma nova.')
            ->action('Repor a password', $this->url)
            ->line('O link expira em ' . $this->minutes . ' minutos. Se não pediu para repor a password, ignore este email: a sua password continua igual.')
            ->salutation('Lar em Ordem');
    }
}