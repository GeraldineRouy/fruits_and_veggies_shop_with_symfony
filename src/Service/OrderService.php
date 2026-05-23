<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Message\OrderStatusChanged;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository         $cartRepository,
        private readonly MailerService          $mailerService,
        private readonly MessageBusInterface    $messageBus,
    )
    {
    }

    public function createFromCart(User $user): Order
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null || $cart->getItems()->isEmpty()) {
            throw new InvalidArgumentException('Votre panier est vide.');
        }

        $order = new Order();
        $order->setUser($user);

        foreach ($cart->getItems() as $cartItem) {
            $quantity = $cartItem->getQuantity();
            if ($quantity === null || $quantity < 1) {
                throw new InvalidArgumentException('Une quantité invalide a été détectée dans le panier.');
            }

            $orderLine = new OrderLine();
            $orderLine->setProduct($cartItem->getProduct());
            $orderLine->setQuantity($quantity);
            $orderLine->setPrice($cartItem->getPrice());

            $order->addOrderLine($orderLine);
            $this->entityManager->persist($orderLine);
        }

        $this->entityManager->persist($order);

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->entityManager->remove($item);
        }

        $this->entityManager->flush();

        $this->messageBus->dispatch(new OrderStatusChanged((int)$order->getId()));

        return $order;
    }

    public function transitionStatus(Order $order, OrderStatus $newStatus): void
    {
        $currentStatus = $order->getStatus();

        if ($currentStatus === null) {
            throw new RuntimeException('La commande n\'a pas de statut.');
        }

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            throw new RuntimeException(
                sprintf(
                    'Transition de statut invalide : de %s vers %s.',
                    $currentStatus->value,
                    $newStatus->value,
                ),
            );
        }

        $order->setStatus($newStatus);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new OrderStatusChanged((int)$order->getId()));
    }

    public function cancelOrder(Order $order, bool $isAdmin = false): void
    {
        $status = $order->getStatus();

        if ($status === null) {
            throw new RuntimeException('La commande n\'a pas de statut.');
        }

        if ($status === OrderStatus::Cancelled) {
            throw new RuntimeException('Cette commande est déjà annulée.');
        }

        if (!$isAdmin && $status !== OrderStatus::Confirmed) {
            throw new RuntimeException(
                sprintf(
                    'Vous ne pouvez pas annuler une commande avec le statut %s.',
                    $status->value,
                ),
            );
        }

        $order->setStatus(OrderStatus::Cancelled);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new OrderStatusChanged((int)$order->getId()));
    }

    public function getOrderTotal(Order $order): string
    {
        $total = 0;

        foreach ($order->getOrderLines() as $line) {
            $price = $line->getPrice();
            $quantity = $line->getQuantity();
            if ($price !== null && $quantity !== null) {
                $total += (float)$price * $quantity;
            }
        }

        return number_format($total, 2, '.', '');
    }

    public function getPossibleNextStatuses(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::Confirmed => [OrderStatus::Preparing],
            OrderStatus::Preparing => [OrderStatus::Shipped],
            OrderStatus::Shipped => [OrderStatus::Delivered],
            OrderStatus::Delivered => [],
            OrderStatus::Cancelled => [],
        };
    }

    private function isValidTransition(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to, $this->getPossibleNextStatuses($from), true);
    }
}
