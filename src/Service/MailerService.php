<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail,
        private readonly string $appBaseUrl,
    ) {
    }

    public function sendValidationEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Fruits & Veggies Shop'))
            ->to(new Address($user->getEmail(), $user->getFirstName()))
            ->subject('Confirmez votre adresse email - Fruits & Veggies Shop')
            ->htmlTemplate('email/validation.html.twig')
            ->textTemplate('email/validation.txt.twig')
            ->context([
                'user' => $user,
                'validationUrl' => sprintf(
                    '%s/verify-email?token=%s',
                    $this->appBaseUrl,
                    $user->getEmailVerificationToken(),
                ),
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Fruits & Veggies Shop'))
            ->to(new Address($user->getEmail(), $user->getFirstName()))
            ->subject('Réinitialisation de votre mot de passe - Fruits & Veggies Shop')
            ->htmlTemplate('email/reset_password.html.twig')
            ->textTemplate('email/reset_password.txt.twig')
            ->context([
                'user' => $user,
                'resetUrl' => sprintf(
                    '%s/reset-password?token=%s',
                    $this->appBaseUrl,
                    $token,
                ),
            ]);

        $this->mailer->send($email);
    }
}
