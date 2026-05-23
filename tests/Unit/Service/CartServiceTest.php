<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Service\CartService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CartServiceTest extends TestCase
{
    private function createService(
        ?EntityManagerInterface $entityManager = null,
        ?CartRepository $cartRepository = null,
        ?OrderService $orderService = null,
    ): CartService {
        return new CartService(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $cartRepository ?? $this->createMock(CartRepository::class),
            $orderService ?? $this->createMock(OrderService::class),
        );
    }

    #[Test]
    public function getOrCreateCartCreatesNewCartWhenNoneExists(): void
    {
        $user = new User();

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(Cart::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
        );

        $cart = $service->getOrCreateCart($user);

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertSame($user, $cart->getUser());
    }

    #[Test]
    public function getOrCreateCartReturnsExistingCart(): void
    {
        $user = new User();
        $existingCart = new Cart();
        $existingCart->setUser($user);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($existingCart);

        $service = $this->createService(cartRepository: $cartRepository);

        $cart = $service->getOrCreateCart($user);

        $this->assertSame($existingCart, $cart);
    }

    #[Test]
    public function addProductCreatesNewCartItem(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);
        $product = new Product();
        $product->setPrice('12.50');

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(CartItem::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
        );

        $service->addProduct($user, $product, 1);

        $this->assertCount(1, $cart->getItems());
        $item = $cart->getItems()->first();
        $this->assertSame($product, $item->getProduct());
        $this->assertSame(1, $item->getQuantity());
        $this->assertSame('12.50', $item->getPrice());
    }

    #[Test]
    public function addProductIncrementsQuantityWhenProductAlreadyInCart(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);
        $product = new Product();
        $product->setPrice('12.50');

        $existingItem = new CartItem();
        $existingItem->setCart($cart);
        $existingItem->setProduct($product);
        $existingItem->setQuantity(2);
        $existingItem->setPrice('12.50');
        $cart->addItem($existingItem);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
        );

        $service->addProduct($user, $product, 1);

        $this->assertSame(3, $existingItem->getQuantity());
    }

    #[Test]
    public function addProductThrowsExceptionWhenQuantityLessThanOne(): void
    {
        $user = new User();
        $product = new Product();
        $service = $this->createService();

        $this->expectException(InvalidArgumentException::class);

        $service->addProduct($user, $product, 0);
    }

    #[Test]
    public function updateItemQuantityModifiesQuantity(): void
    {
        $item = new CartItem();
        $item->setQuantity(3);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(entityManager: $entityManager);

        $service->updateItemQuantity($item, 5);

        $this->assertSame(5, $item->getQuantity());
    }

    #[Test]
    public function updateItemQuantityRemovesItemWhenQuantityIsZero(): void
    {
        $cart = new Cart();
        $item = new CartItem();
        $item->setQuantity(3);
        $item->setCart($cart);
        $cart->addItem($item);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($item);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(entityManager: $entityManager);

        $service->updateItemQuantity($item, 0);

        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function updateItemQuantityThrowsExceptionWhenQuantityNegative(): void
    {
        $item = new CartItem();
        $service = $this->createService();

        $this->expectException(InvalidArgumentException::class);

        $service->updateItemQuantity($item, -1);
    }

    #[Test]
    public function removeItemRemovesCartItem(): void
    {
        $cart = new Cart();
        $item = new CartItem();
        $item->setCart($cart);
        $cart->addItem($item);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($item);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(entityManager: $entityManager);

        $service->removeItem($item);

        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function clearCartRemovesAllItems(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);

        $item1 = new CartItem();
        $item1->setCart($cart);
        $cart->addItem($item1);

        $item2 = new CartItem();
        $item2->setCart($cart);
        $cart->addItem($item2);

        $item3 = new CartItem();
        $item3->setCart($cart);
        $cart->addItem($item3);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            entityManager: $entityManager,
            cartRepository: $cartRepository,
        );

        $service->clearCart($user);

        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function getTotalReturnsCorrectSum(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);

        $product1 = new Product();
        $product1->setPrice('12.50');

        $item1 = new CartItem();
        $item1->setCart($cart);
        $item1->setProduct($product1);
        $item1->setQuantity(2);
        $item1->setPrice('12.50');
        $cart->addItem($item1);

        $product2 = new Product();
        $product2->setPrice('5.00');

        $item2 = new CartItem();
        $item2->setCart($cart);
        $item2->setProduct($product2);
        $item2->setQuantity(1);
        $item2->setPrice('5.00');
        $cart->addItem($item2);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $service = $this->createService(cartRepository: $cartRepository);

        $total = $service->getTotal($user);

        $this->assertSame('30.00', $total);
    }

    #[Test]
    public function getTotalReturnsZeroForEmptyCart(): void
    {
        $user = new User();

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $service = $this->createService(cartRepository: $cartRepository);

        $total = $service->getTotal($user);

        $this->assertSame('0.00', $total);
    }

    #[Test]
    public function getProductCountReturnsTotalQuantity(): void
    {
        $user = new User();
        $cart = new Cart();
        $cart->setUser($user);

        $item1 = new CartItem();
        $item1->setCart($cart);
        $item1->setQuantity(3);
        $cart->addItem($item1);

        $item2 = new CartItem();
        $item2->setCart($cart);
        $item2->setQuantity(2);
        $cart->addItem($item2);

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($cart);

        $service = $this->createService(cartRepository: $cartRepository);

        $count = $service->getProductCount($user);

        $this->assertSame(5, $count);
    }

    #[Test]
    public function getProductCountReturnsZeroForEmptyCart(): void
    {
        $user = new User();

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn(null);

        $service = $this->createService(cartRepository: $cartRepository);

        $count = $service->getProductCount($user);

        $this->assertSame(0, $count);
    }
}
