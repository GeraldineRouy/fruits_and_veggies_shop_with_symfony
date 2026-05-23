<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\MailerService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class MailerServiceTest extends TestCase
{
    private function createService(
        ?MailerInterface $mailer = null,
        string $senderEmail = 'noreply@example.com',
        string $appBaseUrl = 'http://localhost:8000',
    ): MailerService {
        return new MailerService(
            $mailer ?? $this->createMock(MailerInterface::class),
            $senderEmail,
            $appBaseUrl,
        );
    }

    #[Test]
    public function sendValidationEmailConstructsCorrectEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setFirstName('John');
        $user->setEmailVerificationToken('verification_token_123');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user) {
                $this->assertCount(1, $email->getTo());
                $this->assertSame('user@example.com', $email->getTo()[0]->getAddress());
                $this->assertSame('John', $email->getTo()[0]->getName());
                $this->assertCount(1, $email->getFrom());
                $this->assertSame('noreply@example.com', $email->getFrom()[0]->getAddress());
                $this->assertStringContainsString('Confirmez votre adresse email', $email->getSubject());

                $this->assertSame('email/validation.html.twig', $email->getHtmlTemplate());
                $this->assertSame('email/validation.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();
                $this->assertArrayHasKey('validationUrl', $context);
                $this->assertStringContainsString('verification_token_123', $context['validationUrl']);
                $this->assertArrayHasKey('user', $context);
                $this->assertSame($user, $context['user']);

                return true;
            }));

        $service = $this->createService(mailer: $mailer);
        $service->sendValidationEmail($user);
    }

    #[Test]
    public function sendPasswordResetEmailConstructsCorrectEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setFirstName('Jane');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user) {
                $this->assertCount(1, $email->getTo());
                $this->assertSame('user@example.com', $email->getTo()[0]->getAddress());
                $this->assertStringContainsString('Réinitialisation', $email->getSubject());

                $this->assertSame('email/reset_password.html.twig', $email->getHtmlTemplate());
                $this->assertSame('email/reset_password.txt.twig', $email->getTextTemplate());

                $context = $email->getContext();
                $this->assertArrayHasKey('resetUrl', $context);
                $this->assertStringContainsString('reset_token_456', $context['resetUrl']);

                return true;
            }));

        $service = $this->createService(mailer: $mailer);
        $service->sendPasswordResetEmail($user, 'reset_token_456');
    }
}
