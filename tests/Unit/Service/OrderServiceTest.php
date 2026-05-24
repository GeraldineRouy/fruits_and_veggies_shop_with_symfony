<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Message\OrderStatusChanged;
use App\Repository\CartRepository;
use App\Service\MailerService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderServiceTest extends TestCase
{
    private function createService(
        ?EntityManagerInterface $entityManager = null,
        ?CartRepository $cartRepository = null,
        ?MailerService $mailerService = null,
        ?MessageBusInterface $messageBus = null,
    ): OrderService {
        return new OrderService(
            entityManager: $entityManager ?? $this->createMock(EntityManagerInterface::class),
            cartRepository: $cartRepository ?? $this->createMock(CartRepository::class),
            mailerService: $mailerService ?? $this->createMock(MailerService::class),
            messageBus: $messageBus ?? $this->createMock(MessageBusInterface::class),
        );
    }

    private function createCartWithItems(User $user): Cart
    {
        $cart = new Cart();
        $cart->setUser($user);

        $productA = new Product();
        $productA->setName('Pomme');
        $productA->setPrice('5.00');

        $itemA = new CartItem();
        $itemA->setCart($cart);
        $itemA->setProduct($productA);
        $itemA->setQuantity(2);
        $itemA->setPrice('5.00');
        $cart->addItem($itemA);

        $productB = new Product();
        $productB->setName('Banane');
        $productB->setPrice('3.50');

        $itemB = new CartItem();
        $itemB->setCart($cart);
        $itemB->setProduct($productB);
        $itemB->setQuantity(1);
        $itemB->setPrice('3.50');
        $cart->addItem($itemB);

        return $cart;
    }

    #[Test]
    public function createFromCartCreatesOrderWithConfirmedStatus(): void
    {
        $user = new User();
        $cart = $this->createCartWithItems($user);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))->method('persist');
        $entityManager->expects($this->exactly(2))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderStatusChanged::class))
            ->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
            messageBus: $messageBus,
        );

        $order = $service->createFromCart($user);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($user, $order->getUser());
        $this->assertEquals(OrderStatus::Confirmed, $order->getStatus());
        $this->assertNotNull($order->getOrderedAt());
        $this->assertCount(2, $order->getOrderLines());
        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function createFromCartThrowsExceptionWhenCartIsEmpty(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $service = $this->createService(cartRepository: $cartRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Votre panier est vide.');

        $service->createFromCart($user);
    }

    #[Test]
    public function createFromCartThrowsExceptionWhenNoCartExists(): void
    {
        $user = new User();

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $service = $this->createService(cartRepository: $cartRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Votre panier est vide.');

        $service->createFromCart($user);
    }

    #[Test]
    public function getOrderTotalReturnsCorrectSum(): void
    {
        $order = new Order();
        $order->setStatus(OrderStatus::Confirmed);

        $productA = new Product();
        $productA->setPrice('5.00');

        $lineA = new OrderLine();
        $lineA->setProduct($productA);
        $lineA->setQuantity(2);
        $lineA->setPrice('5.00');
        $order->addOrderLine($lineA);

        $productB = new Product();
        $productB->setPrice('3.50');

        $lineB = new OrderLine();
        $lineB->setProduct($productB);
        $lineB->setQuantity(1);
        $lineB->setPrice('3.50');
        $order->addOrderLine($lineB);

        $service = $this->createService();

        $this->assertSame('13.50', $service->getOrderTotal($order));
    }

    #[Test]
    public function getOrderTotalReturnsZeroForEmptyOrder(): void
    {
        $order = new Order();
        $service = $this->createService();

        $this->assertSame('0.00', $service->getOrderTotal($order));
    }

    #[Test]
    public function transitionStatusMovesToNextValidStatus(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Confirmed);
        $order->expects($this->once())
            ->method('setStatus')
            ->with(OrderStatus::Preparing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderStatusChanged::class))
            ->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            messageBus: $messageBus,
        );

        $service->transitionStatus($order, OrderStatus::Preparing);
    }

    #[Test]
    public function transitionStatusThrowsExceptionWhenInvalid(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Confirmed);
        $order->expects($this->never())
            ->method('setStatus');

        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transition de statut invalide');

        $service->transitionStatus($order, OrderStatus::Delivered);
    }

    #[Test]
    public function transitionStatusCompletesFullChain(): void
    {
        $order = $this->createMock(Order::class);
        $matcher = $this->exactly(3);
        $order->expects($matcher)
            ->method('getStatus')
            ->willReturnCallback(fn() => match ($matcher->numberOfInvocations()) {
                1 => OrderStatus::Confirmed,
                2 => OrderStatus::Preparing,
                3 => OrderStatus::Shipped,
                default => throw new \RuntimeException('Unexpected'),
            });
        $order->expects($this->exactly(3))
            ->method('setStatus')
            ->willReturnCallback(function (OrderStatus $s) use ($order) {
                if ($s === OrderStatus::Preparing || $s === OrderStatus::Shipped || $s === OrderStatus::Delivered) {
                    return $order;
                }
                throw new \RuntimeException('Unexpected status');
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->exactly(3))
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderStatusChanged::class))
            ->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            messageBus: $messageBus,
        );

        $service->transitionStatus($order, OrderStatus::Preparing);
        $service->transitionStatus($order, OrderStatus::Shipped);
        $service->transitionStatus($order, OrderStatus::Delivered);
    }

    #[Test]
    public function cancelOrderByClientWhenConfirmed(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Confirmed);
        $order->expects($this->once())
            ->method('setStatus')
            ->with(OrderStatus::Cancelled);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderStatusChanged::class))
            ->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            messageBus: $messageBus,
        );

        $service->cancelOrder($order, isAdmin: false);
    }

    #[Test]
    public function cancelOrderByClientThrowsExceptionWhenNotConfirmed(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Shipped);

        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas annuler une commande avec le statut shipped.');

        $service->cancelOrder($order, isAdmin: false);
    }

    #[Test]
    public function cancelOrderByAdminAnyStatus(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Shipped);
        $order->expects($this->once())
            ->method('setStatus')
            ->with(OrderStatus::Cancelled);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderStatusChanged::class))
            ->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            messageBus: $messageBus,
        );

        $service->cancelOrder($order, isAdmin: true);
    }

    #[Test]
    public function cancelOrderByAdminThrowsExceptionWhenAlreadyCancelled(): void
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getStatus')
            ->willReturn(OrderStatus::Cancelled);

        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cette commande est déjà annulée.');

        $service->cancelOrder($order, isAdmin: true);
    }

    #[Test]
    public function orderLinesHaveAutoGeneratedIds(): void
    {
        $user = new User();
        $cart = $this->createCartWithItems($user);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $persistedEntities = [];
        $entityManager->expects($this->exactly(3))->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });

        $entityManager->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
            messageBus: $messageBus,
        );

        $service->createFromCart($user);

        $orderLines = array_filter($persistedEntities, fn($e) => $e instanceof OrderLine);
        $this->assertCount(2, $orderLines);
        foreach ($orderLines as $orderLine) {
            $this->assertNull($orderLine->getId(), 'OrderLine ID must be null before flush (auto-generated)');
        }
    }

    #[Test]
    public function createFromCartPropagatesUnexpectedExceptions(): void
    {
        $user = new User();
        $cart = $this->createCartWithItems($user);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Erreur Doctrine simulée'));

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur Doctrine simulée');

        $service->createFromCart($user);
    }
}
