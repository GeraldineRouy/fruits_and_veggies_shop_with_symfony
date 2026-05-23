<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Entity\Product;
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

        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
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

    private function createCategory(string $name, string $description): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
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
