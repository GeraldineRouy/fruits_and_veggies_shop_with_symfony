<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OrderProcessTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    private function createUser(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new \App\Entity\User();
        $user->setEmail('e2e@example.com');
        $user->setFirstName('E2E');
        $user->setLastName('Test');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setVerifiedAt(new \DateTimeImmutable());
        $user->setIsActive(true);

        $entityManager->persist($user);
        $entityManager->flush();
    }

    private function createProduct(): Product
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $product = new Product();
        $product->setName('Pomme Bio');
        $product->setPrice('2.50');
        $product->setDescription('Une pomme bio et juteuse');
        $product->setImage('pomme-bio.jpg');

        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }

    private function addToCart(Product $product): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(\App\Entity\User::class)->findOneByEmail('e2e@example.com');

        $cartService = $container->get(CartService::class);
        $cartService->addProduct($user, $product, 2);
    }

    private function login(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'e2e@example.com';
        $form['_password'] = 'password123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
    }

    #[Test]
    public function completeOrderFlow(): void
    {
        $this->createUser();
        $product = $this->createProduct();
        $this->addToCart($product);
        $this->login();

        $crawler = $this->client->request('GET', '/panier');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Mon panier', $crawler->filter('h1')->text());

        $checkoutForm = $crawler->selectButton('Valider la commande')->form();
        $this->client->submit($checkoutForm);
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Commande #', $crawler->filter('h1')->text());
        $this->assertStringContainsString('Confirmée', $crawler->text());

        $crawler = $this->client->request('GET', '/profile/commandes');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Mes commandes', $crawler->filter('h1')->text());
        $this->assertStringContainsString('#', $crawler->text());

        $crawler = $this->client->click($crawler->selectLink('Voir')->link());
        $this->assertResponseIsSuccessful();

        $cancelForm = $crawler->selectButton('Annuler la commande')->form();
        $this->client->submit($cancelForm);
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Annulée', $crawler->text());
    }
}
