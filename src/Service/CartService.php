<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
    ) {}

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function addProduct(User $user, Product $product, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('La quantité doit être au moins 1.');
        }

        $cart = $this->getOrCreateCart($user);

        foreach ($cart->getItems() as $existingItem) {
            if ($existingItem->getProduct() === $product) {
                $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
                $this->entityManager->flush();

                return;
            }
        }

        $item = new CartItem();
        $item->setCart($cart);
        $item->setProduct($product);
        $item->setQuantity($quantity);
        $item->setPrice($product->getPrice());

        $cart->addItem($item);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('La quantité ne peut pas être négative.');
        }

        if ($quantity === 0) {
            $this->removeItem($item);

            return;
        }

        $item->setQuantity($quantity);
        $this->entityManager->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->getCart();

        if ($cart === null) {
            throw new RuntimeException('Cet article ne fait plus partie d\'un panier.');
        }

        $cart->removeItem($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function clearCart(User $user): void
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            return;
        }

        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->entityManager->remove($item);
        }

        $this->entityManager->flush();
    }

    public function getProductCount(User $user): int
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            return 0;
        }

        $count = 0;

        foreach ($cart->getItems() as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function getTotal(User $user): string
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            return '0.00';
        }

        $total = 0;

        foreach ($cart->getItems() as $item) {
            $total += (float) $item->getPrice() * $item->getQuantity();
        }

        return number_format($total, 2, '.', '');
    }

    public function getItems(User $user): array
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            return [];
        }

        return $cart->getItems()->toArray();
    }
}
