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
        public readonly bool $newAccount = true,
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

        $mail = (new MailMessage)
            ->subject('Confirme o seu email - Lar em Ordem')
            ->greeting('Olá, ' . $this->name . '!');

        $mail = $this->newAccount
            ? $mail->line('Recebemos o seu pedido de registo. Clique no botão abaixo para confirmar o email e criar a conta.')
                ->action('Confirmar email e criar conta', $this->url)
            : $mail->line('Clique no botão abaixo para confirmar o email da sua conta.')
                ->action('Confirmar email', $this->url);

        return $mail
            ->line('O link expira em ' . $this->minutes . ' minutos e pode ser aberto em qualquer dispositivo. Se não fez este pedido, ignore este email.')
            ->salutation('Lar em Ordem');
    }
}