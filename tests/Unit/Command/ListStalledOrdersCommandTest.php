<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ListStalledOrdersCommand;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListStalledOrdersCommandTest extends TestCase
{
    #[Test]
    public function listsStalledOrders(): void
    {
        $user = new User();
        $user->setEmail('client@example.com');

        $order = new Order();
        $order->setUser($user);
        $order->setOrderedAt(new DateTimeImmutable('-10 days'));
        $order->setStatus(OrderStatus::Confirmed);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findStalledOrders')
            ->willReturn([$order]);

        $command = new ListStalledOrdersCommand($orderRepository);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('client@example.com', $output);
        $this->assertStringContainsString('confirmed', $output);
    }

    #[Test]
    public function noStalledOrdersShowsMessage(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findStalledOrders')
            ->willReturn([]);

        $command = new ListStalledOrdersCommand($orderRepository);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('Aucune commande', $tester->getDisplay());
    }
}
