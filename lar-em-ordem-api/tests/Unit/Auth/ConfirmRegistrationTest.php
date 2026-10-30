<?php

namespace Tests\Unit\Auth;

use App\Notifications\ConfirmRegistration;
use Tests\TestCase;

/**
 * The email people receive when they register or change their email. User-facing text stays in Portuguese.
 */
class ConfirmRegistrationTest extends TestCase
{
    private function mail(int $minutes = 10)
    {
        return (new ConfirmRegistration('Ana', 'https://app.test/verify-email?token=abc', $minutes))
            ->toMail(new \stdClass());
    }

    public function test_it_is_a_mail_notification_only(): void
    {
        $this->assertSame(['mail'], (new ConfirmRegistration('Ana', 'https://app.test', 10))->via(new \stdClass()));
    }

    public function test_the_button_opens_the_given_link(): void
    {
        $mail = $this->mail();

        $this->assertSame('https://app.test/verify-email?token=abc', $mail->actionUrl);
        $this->assertSame('Confirmar email e criar conta', $mail->actionText);
    }

    public function test_the_email_is_branded_and_greets_the_person_by_name(): void
    {
        $mail = $this->mail();

        $this->assertSame('Confirme o seu email - Lar em Ordem', $mail->subject);
        $this->assertSame('Olá, Ana!', $mail->greeting);
        $this->assertSame('Lar em Ordem', $mail->salutation);
    }

    public function test_it_says_how_long_the_link_lasts(): void
    {
        $this->assertStringContainsString('expira em 10 minutos', implode(' ', $this->mail(10)->outroLines));
        $this->assertStringContainsString('expira em 15 minutos', implode(' ', $this->mail(15)->outroLines));
    }
}