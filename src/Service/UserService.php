<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private const int RESET_TOKEN_EXPIRY_HOURS = 1;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ResetPasswordRequestRepository $resetPasswordRequestRepository,
    ) {
    }

    public function register(User $user, string $plainPassword): User
    {
        if ($this->userRepository->findOneByEmail($user->getEmail()) !== null) {
            throw new RuntimeException('Email already in use');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerificationToken($this->generateToken());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function validateEmail(string $token): User
    {
        $user = $this->userRepository->findOneByEmailVerificationToken($token);

        if ($user === null) {
            throw new RuntimeException('Invalid verification token');
        }

        if ($user->getVerifiedAt() !== null) {
            throw new RuntimeException('Email already verified');
        }

        $user->setVerifiedAt(new DateTimeImmutable());
        $user->setEmailVerificationToken(null);

        $this->entityManager->flush();

        return $user;
    }

    public function isEmailVerified(User $user): bool
    {
        return $user->getVerifiedAt() !== null;
    }

    public function requestPasswordReset(User $user): string
    {
        $token = $this->generateToken();
        $expiresAt = new DateTimeImmutable(sprintf('+%d hours', self::RESET_TOKEN_EXPIRY_HOURS));

        $resetRequest = new ResetPasswordRequest($user, $token, $expiresAt);

        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        return $token;
    }

    public function resetPassword(string $token, string $newPlainPassword): User
    {
        $resetRequest = $this->resetPasswordRequestRepository->findOneByToken($token);

        if ($resetRequest === null) {
            throw new RuntimeException('Invalid reset token');
        }

        if ($resetRequest->isExpired()) {
            throw new RuntimeException('Reset token has expired');
        }

        $user = $resetRequest->getUser();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPlainPassword);

        $user->setPassword($hashedPassword);

        $this->entityManager->remove($resetRequest);
        $this->entityManager->flush();

        return $user;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
