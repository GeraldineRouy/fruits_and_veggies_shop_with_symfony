<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PurgeUnverifiedUsersCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeUnverifiedUsersCommandTest extends TestCase
{
    #[Test]
    public function executeDeletesUnverifiedUsers(): void
    {
        $user = new User();
        $user->setEmail('unverified@example.com');
        $user->setVerifiedAt(null);
        $user->setCreatedAt(new DateTimeImmutable('-10 days'));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findUnverifiedSince')
            ->willReturn([$user]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $command = new PurgeUnverifiedUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function dryRunDoesNotDelete(): void
    {
        $user = new User();
        $user->setEmail('unverified@example.com');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findUnverifiedSince')
            ->willReturn([$user]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $command = new PurgeUnverifiedUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => true]);

        $this->assertStringContainsString('unverified@example.com', $tester->getDisplay());
    }

    #[Test]
    public function noUnverifiedUsersShowsMessage(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findUnverifiedSince')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $command = new PurgeUnverifiedUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('Aucun', $tester->getDisplay());
    }
}
