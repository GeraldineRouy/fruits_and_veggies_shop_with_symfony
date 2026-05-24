<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryRepositoryTest extends KernelTestCase
{
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->categoryRepository = $container->get(CategoryRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
    }

    #[Test]
    public function findAllOrderedReturnsCategoriesSortedByName(): void
    {
        $this->createCategory('Légumes', 'Légumes frais');
        $this->createCategory('Fruits', 'Fruits frais');
        $this->createCategory('Agrumes', 'Agrumes juteux');

        $result = $this->categoryRepository->findAllOrdered();

        $this->assertCount(3, $result);
        $this->assertSame('Agrumes', $result[0]->getName());
        $this->assertSame('Fruits', $result[1]->getName());
        $this->assertSame('Légumes', $result[2]->getName());
    }

    #[Test]
    public function findAllWithProductCountReturnsCategoriesWithCounts(): void
    {
        $fruits = $this->createCategory('Fruits', 'Fruits frais');
        $legumes = $this->createCategory('Légumes', 'Légumes frais');

        $this->createProduct('Pomme', $fruits);
        $this->createProduct('Banane', $fruits);
        $this->createProduct('Orange', $fruits);

        $result = $this->categoryRepository->findAllWithProductCount();

        $this->assertCount(2, $result);
        $this->assertSame('Fruits', $result[0]['category']->getName());
        $this->assertSame(3, $result[0]['productCount']);
        $this->assertSame('Légumes', $result[1]['category']->getName());
        $this->assertSame(0, $result[1]['productCount']);
    }

    #[Test]
    public function findAllWithProductCountReturnsEmptyArrayWhenNoCategories(): void
    {
        $result = $this->categoryRepository->findAllWithProductCount();

        $this->assertSame([], $result);
    }

    #[Test]
    public function findAllOrderedRemainsUnchanged(): void
    {
        $this->createCategory('Fruits', 'Fruits frais');
        $this->createCategory('Légumes', 'Légumes frais');

        $result = $this->categoryRepository->findAllOrdered();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Category::class, $result);
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

    private function createProduct(string $name, Category $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription('Description de ' . $name);
        $product->setImage(strtolower($name) . '.jpg');
        $product->setPrice('2.50');
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
