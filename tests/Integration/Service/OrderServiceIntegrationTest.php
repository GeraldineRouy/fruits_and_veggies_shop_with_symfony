<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderServiceIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CartService $cartService;
    private OrderService $orderService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->cartService = $container->get(CartService::class);
        $this->orderService = $container->get(OrderService::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    private function createUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setVerifiedAt(new \DateTimeImmutable());
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createProduct(string $name = 'Pomme', string $price = '5.00'): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setPrice($price);
        $product->setDescription('Un produit frais');
        $product->setImage('pomme.jpg');

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    #[Test]
    public function fullCartToOrderConversion(): void
    {
        $user = $this->createUser();
        $productA = $this->createProduct('Pomme', '5.00');
        $productB = $this->createProduct('Banane', '3.50');

        $this->cartService->addProduct($user, $productA, 2);
        $this->cartService->addProduct($user, $productB, 1);

        $order = $this->orderService->createFromCart($user);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($user->getId(), $order->getUser()?->getId());
        $this->assertEquals(OrderStatus::Confirmed, $order->getStatus());
        $this->assertNotNull($order->getOrderedAt());

        $this->assertCount(2, $order->getOrderLines());

        $total = $this->orderService->getOrderTotal($order);
        $this->assertSame('13.50', $total);

        $cartItems = $this->cartService->getItems($user);
        $this->assertCount(0, $cartItems);
    }

    #[Test]
    public function statusTransitionChainPersistsInDatabase(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();

        $this->cartService->addProduct($user, $product, 1);
        $order = $this->orderService->createFromCart($user);

        $this->orderService->transitionStatus($order, OrderStatus::Preparing);

        $this->entityManager->refresh($order);
        $this->assertEquals(OrderStatus::Preparing, $order->getStatus());

        $this->orderService->transitionStatus($order, OrderStatus::Shipped);

        $this->entityManager->refresh($order);
        $this->assertEquals(OrderStatus::Shipped, $order->getStatus());

        $this->orderService->transitionStatus($order, OrderStatus::Delivered);

        $this->entityManager->refresh($order);
        $this->assertEquals(OrderStatus::Delivered, $order->getStatus());
    }

    #[Test]
    public function cancelOrderByClientPersistsInDatabase(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();

        $this->cartService->addProduct($user, $product, 1);
        $order = $this->orderService->createFromCart($user);

        $this->orderService->cancelOrder($order, isAdmin: false);

        $this->entityManager->refresh($order);
        $this->assertEquals(OrderStatus::Cancelled, $order->getStatus());
    }

    #[Test]
    public function cancelOrderByAdminOnDeliveredOrder(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();

        $this->cartService->addProduct($user, $product, 1);
        $order = $this->orderService->createFromCart($user);

        $this->orderService->transitionStatus($order, OrderStatus::Preparing);
        $this->orderService->transitionStatus($order, OrderStatus::Shipped);
        $this->orderService->transitionStatus($order, OrderStatus::Delivered);

        $this->orderService->cancelOrder($order, isAdmin: true);

        $this->entityManager->refresh($order);
        $this->assertEquals(OrderStatus::Cancelled, $order->getStatus());
    }
}
