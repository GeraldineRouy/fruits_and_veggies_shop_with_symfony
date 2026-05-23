<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->entityManager = $entityManager;
    }

    #[Test]
    public function homepageIsSuccessful(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bienvenue chez Fruits & Veggies');
    }

    #[Test]
    public function homepageUsesBaseTemplate(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsStringIgnoringCase('<!DOCTYPE html>', $this->client->getResponse()->getContent());
    }

    #[Test]
    public function homepageShowsTopProductsWhenOrdersExist(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', $category);
        $p2 = $this->createProduct('Banane', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 5);
        $this->createOrderWithProduct($user, $p2, 3);

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.top-products');
        $this->assertSelectorExists('.top-product-card');
    }

    #[Test]
    public function homepageDoesNotShowTopProductsWhenNoOrders(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProduct('Pomme', $category);
        $this->createProduct('Banane', $category);

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.top-products');
    }

    #[Test]
    public function topProductsAreInCorrectOrder(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $p1 = $this->createProduct('Pomme', $category);
        $p2 = $this->createProduct('Banane', $category);

        $user = $this->createUser('test@example.com');
        $this->createOrderWithProduct($user, $p1, 10);
        $this->createOrderWithProduct($user, $p2, 3);

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $posPomme = strpos($content, 'Pomme');
        $posBanane = strpos($content, 'Banane');
        $this->assertNotFalse($posPomme, 'Pomme should be present');
        $this->assertNotFalse($posBanane, 'Banane should be present');
        $this->assertLessThan($posBanane, $posPomme, 'Pomme should appear before Banane');
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
}
