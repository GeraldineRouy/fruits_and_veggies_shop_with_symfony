<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TailwindStylingTest extends WebTestCase
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
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->entityManager = $entityManager;
    }

    #[Test]
    public function tailwindCdnScriptIsPresent(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'https://cdn.tailwindcss.com',
            $this->client->getResponse()->getContent()
        );
    }

    #[Test]
    public function tailwindCdnScriptIsPresentOnOtherPages(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProduct('Pomme', $category);

        $this->client->request('GET', '/boutique/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'https://cdn.tailwindcss.com',
            $this->client->getResponse()->getContent()
        );
    }

    #[Test]
    public function headerHasTailwindClasses(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('bg-brand-700', $this->client->getResponse()->getContent());
    }

    #[Test]
    public function mainContainerHasTailwindClasses(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('container', $content);
        $this->assertStringContainsString('mx-auto', $content);
    }

    #[Test]
    public function productCardHasTailwindClasses(): void
    {
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $this->createProduct('Pomme', $category);

        $this->client->request('GET', '/boutique/' . $category->getId());

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('rounded-xl', $content);
        $this->assertStringContainsString('shadow-md', $content);
    }

    #[Test]
    public function cartPreviewRouteRequiresAuth(): void
    {
        $this->client->request('GET', '/panier/preview');

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function cartPreviewReturnsCartContent(): void
    {
        $user = $this->createUser('test@example.com');
        $category = $this->createCategory('Fruits', 'Fruits frais');
        $product = $this->createProduct('Pomme', $category);

        $cartService = static::getContainer()->get(CartService::class);
        $cartService->addProduct($user, $product, 2);

        $this->client->loginUser($user);
        $this->client->request('GET', '/panier/preview');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Pomme', $content);
        $this->assertStringContainsString('Mon panier', $content);
        $this->assertStringContainsString('Voir le panier', $content);
        $this->assertStringContainsString('rounded-xl', $content);
        $this->assertStringContainsString('shadow-xl', $content);
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
}
