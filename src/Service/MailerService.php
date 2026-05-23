<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string          $senderEmail,
        private readonly string          $appBaseUrl,
    )
    {
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

    public function sendOrderConfirmationEmail(Order $order): void
    {
        $total = $this->formatOrderTotal($order);

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Fruits & Veggies Shop'))
            ->to(new Address(
                $order->getUser()?->getEmail() ?? '',
                $order->getUser()?->getFirstName() ?? '',
            ))
            ->subject(sprintf(
                'Confirmation de votre commande #%d — Fruits & Veggies Shop',
                $order->getId(),
            ))
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->textTemplate('email/order_confirmation.txt.twig')
            ->context([
                'order' => $order,
                'total' => $total,
            ]);

        $this->mailer->send($email);
    }

    public function sendOrderStatusChangeEmail(Order $order): void
    {
        $status = $order->getStatus();
        $statusLabel = $this->getStatusLabel($status);
        $total = $this->formatOrderTotal($order);

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Fruits & Veggies Shop'))
            ->to(new Address(
                $order->getUser()?->getEmail() ?? '',
                $order->getUser()?->getFirstName() ?? '',
            ))
            ->subject(sprintf(
                'Votre commande #%d est maintenant %s — Fruits & Veggies Shop',
                $order->getId(),
                $statusLabel,
            ))
            ->htmlTemplate('email/order_status_change.html.twig')
            ->textTemplate('email/order_status_change.txt.twig')
            ->context([
                'order' => $order,
                'total' => $total,
                'status_label' => $statusLabel,
            ]);

        $this->mailer->send($email);
    }

    private function formatOrderTotal(Order $order): string
    {
        $total = 0;

        foreach ($order->getOrderLines() as $line) {
            $price = $line->getPrice();
            $quantity = $line->getQuantity();

            if ($price !== null && $quantity !== null) {
                $total += (float)$price * $quantity;
            }
        }

        return number_format($total, 2, '.', '');
    }

    private function getStatusLabel(?OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Confirmed => 'confirmée',
            OrderStatus::Preparing => 'en préparation',
            OrderStatus::Shipped => 'expédiée',
            OrderStatus::Delivered => 'livrée',
            OrderStatus::Cancelled => 'annulée',
            null => 'inconnu',
        };
    }
}
