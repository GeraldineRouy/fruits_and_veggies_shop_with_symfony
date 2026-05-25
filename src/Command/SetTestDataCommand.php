<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:seed',
    description: 'Crée des données de test pour les commandes de purge/orders',
)]
class SetTestDataCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $password = password_hash('test', PASSWORD_BCRYPT);

        $user1 = (new User())
            ->setEmail('inactive@test.com')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setPassword($password)
            ->setLastLoginAt(new DateTimeImmutable('-3 years'));

        $user2 = (new User())
            ->setEmail('unverified@test.com')
            ->setFirstName('Jane')
            ->setLastName('Doe')
            ->setPassword($password)
            ->setCreatedAt(new DateTimeImmutable('-14 days'))
            ->setEmailVerificationToken('test-token');

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        $io->success('Données de test créées :');
        $io->writeln('  - inactive@test.com     (last_login = -3 ans)');
        $io->writeln('  - unverified@test.com   (created_at = -14 jours, verified_at = NULL)');

        $io->newLine();
        $io->writeln('Lance maintenant les commandes :');
        $io->writeln('  php bin/console app:users:purge-inactive   --dry-run');
        $io->writeln('  php bin/console app:users:purge-unverified --dry-run');

        return Command::SUCCESS;
    }
}
