<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
/**
 * Email with a button that links to the frontend to confirm an address. Used when registering
 * (sent on demand to an address that has no user yet) and when the email is changed in the profile.
 */
class ConfirmRegistration extends Notification
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
        if (config('mail.default') === 'log') {
            Log::info('Email confirmation link: ' . $this->url);
        }

        return (new MailMessage)
            ->subject('Confirme o seu email - Lar em Ordem')
            ->greeting('Olá, ' . $this->name . '!')
            ->line('Recebemos o seu pedido de registo. Clique no botão abaixo para confirmar o email e criar a conta.')
            ->action('Confirmar email e criar conta', $this->url)
            ->line('O link expira em ' . $this->minutes . ' minutos e pode ser aberto em qualquer dispositivo. Se não pediu este registo, ignore este email.')
            ->salutation('Lar em Ordem');
    }
}