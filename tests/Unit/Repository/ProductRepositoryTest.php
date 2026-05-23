<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->productRepository = $container->get(ProductRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
    }

    #[Test]
    public function findByCategoryPaginatedReturnsPaginator(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProducts($category, 5);

        $result = $this->productRepository->findByCategoryPaginated($category, 1, 12);

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertCount(5, $result);
    }

    #[Test]
    public function findByCategoryPaginatedWithPagination(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProducts($category, 25);

        $page1 = $this->productRepository->findByCategoryPaginated($category, 1, 12);
        $this->assertCount(12, iterator_to_array($page1));

        $page3 = $this->productRepository->findByCategoryPaginated($category, 3, 12);
        $this->assertCount(1, iterator_to_array($page3));
    }

    #[Test]
    public function findByCategoryPaginatedWithCustomLimit(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProducts($category, 10);

        $result = $this->productRepository->findByCategoryPaginated($category, 1, 5);

        $this->assertCount(5, iterator_to_array($result));
    }

    #[Test]
    public function findByCategoryPaginatedEmptyCategory(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');

        $result = $this->productRepository->findByCategoryPaginated($category, 1, 12);

        $this->assertCount(0, iterator_to_array($result));
    }

    #[Test]
    public function findByCategoryPaginatedThrowsExceptionForInvalidPage(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');

        $this->expectException(\InvalidArgumentException::class);

        $this->productRepository->findByCategoryPaginated($category, 0, 12);
    }

    #[Test]
    public function findTopMostOrderedReturnsTopThreeInOrder(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', '2.50', $category);
        $p2 = $this->createProduct('Banane', '1.20', $category);
        $p3 = $this->createProduct('Orange', '3.00', $category);
        $p4 = $this->createProduct('Kiwi', '2.00', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 10);
        $this->createOrderWithProduct($user, $p2, 5);
        $this->createOrderWithProduct($user, $p3, 3);
        // p4 has no orders

        $result = $this->productRepository->findTopMostOrdered(3);

        $this->assertCount(3, $result);
        $this->assertSame('Pomme', $result[0]->getName());
        $this->assertSame('Banane', $result[1]->getName());
        $this->assertSame('Orange', $result[2]->getName());
    }

    #[Test]
    public function findTopMostOrderedWithLimitOne(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', '2.50', $category);
        $p2 = $this->createProduct('Banane', '1.20', $category);
        $p3 = $this->createProduct('Orange', '3.00', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 10);
        $this->createOrderWithProduct($user, $p2, 5);
        $this->createOrderWithProduct($user, $p3, 3);

        $result = $this->productRepository->findTopMostOrdered(1);

        $this->assertCount(1, $result);
        $this->assertSame('Pomme', $result[0]->getName());
    }

    #[Test]
    public function findTopMostOrderedWithFewerProductsThanLimit(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', '2.50', $category);
        $p2 = $this->createProduct('Banane', '1.20', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 10);
        $this->createOrderWithProduct($user, $p2, 5);

        $result = $this->productRepository->findTopMostOrdered(5);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function findTopMostOrderedWithNoOrders(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProduct('Pomme', '2.50', $category);
        $this->createProduct('Banane', '1.20', $category);
        $this->createProduct('Orange', '3.00', $category);

        $result = $this->productRepository->findTopMostOrdered(3);

        $this->assertCount(0, $result);
    }

    #[Test]
    public function findTopMostOrderedAggregatesSameProduct(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', '2.50', $category);
        $p2 = $this->createProduct('Banane', '1.20', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 3);
        $this->createOrderWithProduct($user, $p1, 7); // same product, different order
        $this->createOrderWithProduct($user, $p2, 5);

        $result = $this->productRepository->findTopMostOrdered(2);

        $this->assertCount(2, $result);
        $this->assertSame('Pomme', $result[0]->getName());
        $this->assertSame('Banane', $result[1]->getName());
    }

    #[Test]
    public function findTopMostOrderedThrowsExceptionForInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->productRepository->findTopMostOrdered(0);
    }

    private function createCategory(string $name, string $description): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $name, string $price, Category $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription('Description de ' . $name);
        $product->setImage(strtolower($name) . '.jpg');
        $product->setPrice($price);
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createOrderWithProduct(User $user, Product $product, int $quantity): void
    {
        $order = new Order();
        $order->setUser($user);
        $this->entityManager->persist($order);

        $orderLine = new OrderLine();
        $orderLine->setOrder($order);
        $orderLine->setProduct($product);
        $orderLine->setQuantity($quantity);
        $orderLine->setPrice($product->getPrice() ?? '0.00');
        $this->entityManager->persist($orderLine);

        $this->entityManager->flush();
    }

    private function createProducts(Category $category, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $product = new Product();
            $product->setName(sprintf('Produit %02d', $i));
            $product->setDescription(sprintf('Description du produit %d', $i));
            $product->setImage(sprintf('produit-%d.jpg', $i));
            $product->setPrice(sprintf('%d.50', $i));
            $product->addCategory($category);
            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();
    }
}
