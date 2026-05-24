<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductImageMigrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDir = static::getContainer()->getParameter('kernel.project_dir');

        $this->entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
    }

    #[Test]
    public function noJpgFilesInProductsDirectory(): void
    {
        $files = glob($this->projectDir . '/public/assets/images/products/*.jpg');

        $this->assertEmpty($files, 'Le dossier products ne doit plus contenir de fichiers .jpg');
    }

    #[Test]
    public function productWithPngImageHasValidPath(): void
    {
        $product = new Product();
        $product->setName('Test PNG');
        $product->setDescription('Produit avec image PNG');
        $product->setImage('assets/images/products/pommes.png');
        $product->setPrice('2.50');

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $saved = $this->entityManager->getRepository(Product::class)->find($product->getId());

        $this->assertNotNull($saved);
        $this->assertNotNull($saved->getImage());
        $this->assertStringEndsWith('.png', $saved->getImage());

        $filePath = $this->projectDir . '/public/' . $saved->getImage();
        $this->assertFileExists($filePath);
    }

    #[Test]
    public function productWithoutImageCanBePersisted(): void
    {
        $product = new Product();
        $product->setName('Test sans image');
        $product->setDescription('Produit sans image de test');
        $product->setImage(null);
        $product->setPrice('1.00');

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $saved = $this->entityManager->getRepository(Product::class)->find($product->getId());

        $this->assertNotNull($saved);
        $this->assertNull($saved->getImage());
    }
}
