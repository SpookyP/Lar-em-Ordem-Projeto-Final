<?php

namespace App\Notifications\Condominium;

use App\Models\Condominium\CondominiumNotice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email aos residentes do prédio quando o administrador publica um aviso ou o atualiza.
 */
class CondominiumNoticePublished extends Notification
{
    public function __construct(
        private CondominiumNotice $notice,
        private bool $updated = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $condominium = $this->notice->condominium;
        $prefix = $this->updated ? 'Aviso atualizado' : 'Novo aviso';

        $mail = (new MailMessage)
            ->subject("{$condominium->name} - {$prefix}: {$this->notice->title}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line($this->updated
                ? "Um aviso do condomínio {$condominium->name} foi atualizado."
                : "Há um novo aviso do condomínio {$condominium->name}.")
            ->line("**{$this->notice->title}**")
            ->line($this->notice->message);

        if ($this->notice->expires_at) {
            $mail->line('Válido até ' . $this->notice->expires_at->format('d/m/Y H:i') . '.');
        }

        if ($this->notice->priority === 'high') {
            $mail->priority(1);
        }

        return $mail
            ->action('Ver no Lar em Ordem', config('oauth.frontend_url'))
            ->salutation('Lar em Ordem');
    }
}