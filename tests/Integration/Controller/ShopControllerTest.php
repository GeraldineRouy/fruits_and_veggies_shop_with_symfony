<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShopControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
    }

    #[Test]
    public function homePageDisplaysCategories(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $category = new Category();
        $category->setName('Fruits');
        $category->setDescription('Fruits frais');
        $entityManager->persist($category);
        $entityManager->flush();

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.category-card');
    }

    #[Test]
    public function categoryPageShowsProducts(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $category = $this->createCategory($entityManager, 'Fruits', 'Fruits frais');
        $this->createProduct($entityManager, 'Pomme', $category);
        $this->createProduct($entityManager, 'Poire', $category);
        $this->createProduct($entityManager, 'Banane', $category);

        $this->client->request('GET', '/boutique/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-card');
    }

    #[Test]
    public function categoryPageNotFound(): void
    {
        $this->client->request('GET', '/boutique/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function productDetailPage(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $category = $this->createCategory($entityManager, 'Fruits', 'Fruits frais');
        $product = $this->createProduct($entityManager, 'Pomme Golden', $category);

        $this->client->request('GET', '/boutique/produit/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Pomme Golden');
    }

    #[Test]
    public function productDetailNotFound(): void
    {
        $this->client->request('GET', '/boutique/produit/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function categoryPageWithPagination(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $category = $this->createCategory($entityManager, 'Fruits', 'Fruits frais');
        for ($i = 1; $i <= 25; $i++) {
            $this->createProduct($entityManager, sprintf('Produit %02d', $i), $category);
        }

        $this->client->request('GET', '/boutique/' . $category->getId() . '?page=2');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-card');
    }

    private function createCategory(EntityManagerInterface $entityManager, string $name, string $description): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    private function createProduct(EntityManagerInterface $entityManager, string $name, Category $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription('Description de ' . $name);
        $product->setImage(strtolower(str_replace(' ', '-', $name)) . '.jpg');
        $product->setPrice('2.50');
        $product->addCategory($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }
}
