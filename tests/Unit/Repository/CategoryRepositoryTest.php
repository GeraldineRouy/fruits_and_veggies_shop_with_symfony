<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Category;
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

    private function createCategory(string $name, string $description): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
