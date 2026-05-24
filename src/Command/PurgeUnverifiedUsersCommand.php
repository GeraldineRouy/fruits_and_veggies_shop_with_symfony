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
    name: 'app:users:purge-unverified',
    description: 'Supprime les comptes non validés après 7 jours',
)]
class PurgeUnverifiedUsersCommand extends Command
{
    private const int UNVERIFIED_DAYS = 7;

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

        $before = new DateTimeImmutable(sprintf('-%d days', self::UNVERIFIED_DAYS));
        $users = $this->userRepository->findUnverifiedSince($before);

        if ($users === []) {
            $io->success('Aucun compte non validé trouvé.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Utilisateurs trouvés : %d', count($users)));

        if ($dryRun) {
            foreach ($users as $user) {
                $io->writeln(sprintf('  - %s (%s %s, inscrit le : %s)',
                    $user->getEmail(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getCreatedAt()?->format('d/m/Y') ?? 'N/A',
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
