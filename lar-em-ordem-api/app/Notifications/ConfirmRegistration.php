<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
/**
 * Email com o link que cria a conta. Enviado para um email que ainda nao tem utilizador
 * (por isso e usado com Notification::route('mail', ...)).
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
            Log::info('Link de confirmacao de email: ' . $this->url);
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