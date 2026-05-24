<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EnrichedProductDataTest extends KernelTestCase
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
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function categoryProduitsLocauxExceptionContainsFiveProducts(): void
    {
        $category = $this->createCategory('Produits locaux d\'exception', 'Produits régionaux');

        $productNames = [
            'Noix de Grenoble AOC',
            'Huile de noix de Grenoble AOC',
            'Fromage Bleu du Vercors-Sassenage',
            'Fromage Saint-Marcellin',
            'Chocolat Bonnat',
        ];

        $products = [];
        foreach ($productNames as $name) {
            $products[] = $this->createProduct($name, $category);
        }

        $this->entityManager->clear();

        $savedCategory = $this->entityManager
            ->getRepository(Category::class)
            ->findOneBy(['name' => 'Produits locaux d\'exception']);

        $this->assertNotNull($savedCategory, 'La catégorie "Produits locaux d\'exception" devrait exister');
        $this->assertCount(5, $savedCategory->getProducts());

        $savedNames = $savedCategory->getProducts()->map(
            static fn (Product $p): string => $p->getName(),
        )->toArray();

        foreach ($productNames as $name) {
            $this->assertContains($name, $savedNames, "Le produit \"$name\" devrait être dans la catégorie");
        }
    }

    #[Test]
    public function categoryLegumesBioNoLongerExists(): void
    {
        $result = $this->entityManager
            ->getRepository(Category::class)
            ->findOneBy(['name' => 'Légumes bio']);

        $this->assertNull($result, 'La catégorie "Légumes bio" ne devrait plus exister');
    }

    #[Test]
    public function productsThatWereInLegumesBioStillExist(): void
    {
        $categoryLegumes = $this->createCategory('Légumes', 'Légumes frais');

        $carotte = $this->createProduct('Carotte', $categoryLegumes);
        $tomate = $this->createProduct('Tomate', $categoryLegumes);
        $courgette = $this->createProduct('Courgette', $categoryLegumes);

        $this->entityManager->clear();

        $carotteSaved = $this->entityManager->getRepository(Product::class)->find($carotte->getId());
        $this->assertNotNull($carotteSaved, 'La Carotte devrait toujours exister');
        $this->assertSame('Carotte', $carotteSaved->getName());

        $tomateSaved = $this->entityManager->getRepository(Product::class)->find($tomate->getId());
        $this->assertNotNull($tomateSaved, 'La Tomate devrait toujours exister');
        $this->assertSame('Tomate', $tomateSaved->getName());

        $courgetteSaved = $this->entityManager->getRepository(Product::class)->find($courgette->getId());
        $this->assertNotNull($courgetteSaved, 'La Courgette devrait toujours exister');
        $this->assertSame('Courgette', $courgetteSaved->getName());
    }

    #[Test]
    public function topThreeProductsAreFraiseSaintMarcellinAnanas(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');

        $fraise = $this->createProduct('Fraise', $category);
        $ananas = $this->createProduct('Ananas', $category);
        $saintMarcellin = $this->createProduct('Fromage Saint-Marcellin', $category);
        $pomme = $this->createProduct('Pomme Golden', $category);

        $user = $this->createUser();

        $this->createOrderWithProduct($user, $fraise, 80);
        $this->createOrderWithProduct($user, $saintMarcellin, 55);
        $this->createOrderWithProduct($user, $ananas, 25);
        $this->createOrderWithProduct($user, $pomme, 5);

        $this->entityManager->clear();

        $topProducts = $this->productRepository->findTopMostOrdered(3);

        $this->assertCount(3, $topProducts);
        $this->assertSame('Fraise', $topProducts[0]->getName());
        $this->assertSame('Fromage Saint-Marcellin', $topProducts[1]->getName());
        $this->assertSame('Ananas', $topProducts[2]->getName());
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
        $product->setImage(strtolower(str_replace(' ', '-', $name)) . '.jpg');
        $product->setPrice('5.00');
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
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
}
