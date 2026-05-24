<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PurgeInactiveUsersCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeInactiveUsersCommandTest extends TestCase
{
    #[Test]
    public function dryRunShowsUsersWithoutDeleting(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setFirstName('Old');
        $user->setLastName('User');
        $user->setLastLoginAt(new DateTimeImmutable('-3 years'));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findInactiveSince')
            ->willReturn([$user]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $command = new PurgeInactiveUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('old@example.com', $output);
    }

    #[Test]
    public function executeDeletesInactiveUsers(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findInactiveSince')
            ->willReturn([$user]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $command = new PurgeInactiveUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function noInactiveUsersShowsMessage(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findInactiveSince')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $command = new PurgeInactiveUsersCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Aucun', $output);
    }
}
