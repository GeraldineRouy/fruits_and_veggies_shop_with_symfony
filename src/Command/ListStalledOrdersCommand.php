<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:orders:list-stalled',
    description: 'Liste les commandes non livrées depuis plus de 7 jours',
)]
class ListStalledOrdersCommand extends Command
{
    private const int STALLED_DAYS = 7;

    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $before = new DateTimeImmutable(sprintf('-%d days', self::STALLED_DAYS));
        $orders = $this->orderRepository->findStalledOrders($before);

        if ($orders === []) {
            $io->success('Aucune commande en attente de livraison.');

            return Command::SUCCESS;
        }

        $io->table(
            ['ID', 'Client', 'Date commande', 'Statut', 'Jours d\'attente'],
            array_map(fn ($order) => [
                $order->getId(),
                $order->getUser()?->getEmail() ?? 'N/A',
                $order->getOrderedAt()?->format('d/m/Y H:i') ?? 'N/A',
                $order->getStatus()?->value ?? 'N/A',
                $order->getOrderedAt() !== null
                    ? (int) $order->getOrderedAt()->diff(new DateTimeImmutable())->days
                    : 'N/A',
            ], $orders),
        );

        $io->success(sprintf('%d commande(s) en attente.', count($orders)));

        return Command::SUCCESS;
    }
}
