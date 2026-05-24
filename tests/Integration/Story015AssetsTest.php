<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Story015AssetsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->entityManager = $entityManager;
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');
    }

    #[Test]
    public function productWithImageRendersPngInCard(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $product = new Product();
        $product->setName('Pomme');
        $product->setDescription('Description');
        $product->setImage('assets/images/products/pommes.png');
        $product->setPrice('2.50');
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/boutique/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('assets/images/products/pommes.png', $content);
        $this->assertStringContainsString('Image de Pomme', $content);
    }

    #[Test]
    public function noJpgFilesInProductsDirectory(): void
    {
        $files = glob($this->projectDir . '/public/assets/images/products/*.jpg');

        $this->assertEmpty($files, 'Le dossier products ne doit plus contenir de fichiers .jpg');
    }

    #[Test]
    public function productWithoutImageShowsNotAvailable(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $product = new Product();
        $product->setName('Test sans image');
        $product->setDescription('Description');
        $product->setImage(null);
        $product->setPrice('1.00');
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/boutique/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Image non disponible', $content);
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
