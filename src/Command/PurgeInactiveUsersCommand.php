<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:users:purge-inactive',
    description: 'Supprime les comptes inactifs depuis plus de 2 ans',
)]
class PurgeInactiveUsersCommand extends Command
{
    private const int INACTIVITY_YEARS = 2;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule la suppression sans modifier la base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $before = new DateTimeImmutable(sprintf('-%d years', self::INACTIVITY_YEARS));
        $users = $this->userRepository->findInactiveSince($before);

        if ($users === []) {
            $io->success('Aucun utilisateur inactif trouvé.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Utilisateurs trouvés : %d', count($users)));

        if ($dryRun) {
            foreach ($users as $user) {
                $io->writeln(sprintf('  - %s (%s %s, dernière connexion : %s)',
                    $user->getEmail(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getLastLoginAt()?->format('d/m/Y') ?? 'jamais',
                ));
            }

            $io->success('Dry-run terminé. Aucune modification en base.');
        } else {
            $this->entityManager->wrapInTransaction(function () use ($users): void {
                foreach ($users as $user) {
                    $this->entityManager->remove($user);
                }
            });

            $io->success(sprintf('%d utilisateur(s) supprimé(s).', count($users)));
        }

        return Command::SUCCESS;
    }
}
