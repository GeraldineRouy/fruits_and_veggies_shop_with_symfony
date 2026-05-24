<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserServiceTest extends TestCase
{
    private function createService(
        ?UserPasswordHasherInterface $passwordHasher = null,
        ?EntityManagerInterface $entityManager = null,
        ?UserRepository $userRepository = null,
        ?ResetPasswordRequestRepository $resetPasswordRequestRepository = null,
    ): UserService {
        return new UserService(
            $passwordHasher ?? $this->createMock(UserPasswordHasherInterface::class),
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $userRepository ?? $this->createMock(UserRepository::class),
            $resetPasswordRequestRepository ?? $this->createMock(ResetPasswordRequestRepository::class),
        );
    }

    #[Test]
    public function registerHashesPasswordAndSetsRole(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmail')
            ->willReturn(null);

        $service = $this->createService(
            passwordHasher: $passwordHasher,
            entityManager: $entityManager,
            userRepository: $userRepository,
        );

        $user = new User();
        $user->setEmail('test@example.com');

        $registered = $service->register($user, 'plain_password');

        $this->assertSame('hashed_password', $registered->getPassword());
        $this->assertContains('ROLE_USER', $registered->getRoles());
        $this->assertNotNull($registered->getEmailVerificationToken());
    }

    #[Test]
    public function registerWithDuplicateEmailThrowsException(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('test@example.com');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($existingUser);

        $service = $this->createService(userRepository: $userRepository);

        $user = new User();
        $user->setEmail('test@example.com');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email already in use');

        $service->register($user, 'plain_password');
    }

    #[Test]
    public function validateEmailWithValidTokenSetsVerifiedAt(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setEmailVerificationToken('valid_token');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmailVerificationToken')
            ->with('valid_token')
            ->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
            userRepository: $userRepository,
        );

        $result = $service->validateEmail('valid_token');

        $this->assertNotNull($result->getVerifiedAt());
        $this->assertNull($result->getEmailVerificationToken());
    }

    #[Test]
    public function validateEmailWithInvalidTokenThrowsException(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmailVerificationToken')
            ->with('invalid_token')
            ->willReturn(null);

        $service = $this->createService(userRepository: $userRepository);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid verification token');

        $service->validateEmail('invalid_token');
    }

    #[Test]
    public function validateEmailWithAlreadyVerifiedUserThrowsException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setEmailVerificationToken('token');
        $user->setVerifiedAt(new DateTimeImmutable());

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneByEmailVerificationToken')
            ->with('token')
            ->willReturn($user);

        $service = $this->createService(userRepository: $userRepository);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email already verified');

        $service->validateEmail('token');
    }

    #[Test]
    public function resetPasswordWithValidTokenHashesAndRemovesRequest(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $expiresAt = (new DateTimeImmutable())->modify('+1 hour');
        $resetRequest = new ResetPasswordRequest($user, 'valid_token', $expiresAt);

        $resetPasswordRequestRepository = $this->createMock(ResetPasswordRequestRepository::class);
        $resetPasswordRequestRepository->expects($this->once())
            ->method('findOneByToken')
            ->with('valid_token')
            ->willReturn($resetRequest);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'new_password')
            ->willReturn('new_hashed_password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($resetRequest);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            passwordHasher: $passwordHasher,
            entityManager: $entityManager,
            resetPasswordRequestRepository: $resetPasswordRequestRepository,
        );

        $result = $service->resetPassword('valid_token', 'new_password');

        $this->assertSame('new_hashed_password', $result->getPassword());
    }

    #[Test]
    public function resetPasswordWithExpiredTokenThrowsException(): void
    {
        $user = new User();
        $expiresAt = (new DateTimeImmutable())->modify('-1 hour');
        $resetRequest = new ResetPasswordRequest($user, 'expired_token', $expiresAt);

        $resetPasswordRequestRepository = $this->createMock(ResetPasswordRequestRepository::class);
        $resetPasswordRequestRepository->expects($this->once())
            ->method('findOneByToken')
            ->with('expired_token')
            ->willReturn($resetRequest);

        $service = $this->createService(
            resetPasswordRequestRepository: $resetPasswordRequestRepository,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Reset token has expired');

        $service->resetPassword('expired_token', 'new_password');
    }

    #[Test]
    public function resetPasswordWithInvalidTokenThrowsException(): void
    {
        $resetPasswordRequestRepository = $this->createMock(ResetPasswordRequestRepository::class);
        $resetPasswordRequestRepository->expects($this->once())
            ->method('findOneByToken')
            ->with('invalid_token')
            ->willReturn(null);

        $service = $this->createService(
            resetPasswordRequestRepository: $resetPasswordRequestRepository,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid reset token');

        $service->resetPassword('invalid_token', 'new_password');
    }

    #[Test]
    public function requestPasswordResetCreatesResetRequest(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(ResetPasswordRequest::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
        );

        $token = $service->requestPasswordReset($user);

        $this->assertNotNull($token);
        $this->assertSame(64, strlen($token));
    }

    #[Test]
    public function deactivateUserTogglesActiveToFalse(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(entityManager: $entityManager);

        $user = new User();
        $user->setIsActive(true);

        $service->deactivateUser($user);

        $this->assertFalse($user->isActive());
    }

    #[Test]
    public function deactivateUserTogglesInactiveToTrue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(entityManager: $entityManager);

        $user = new User();
        $user->setIsActive(false);

        $service->deactivateUser($user);

        $this->assertTrue($user->isActive());
    }
}
