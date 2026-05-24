<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CheckoutControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category ca')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function accessPaymentWithoutAuthenticationRedirectsToLogin(): void
    {
        $this->client->request('GET', '/commande/paiement');

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function accessPaymentWithEmptyCartRedirectsToCart(): void
    {
        $user = $this->createUser('buyer@test.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/commande/paiement');

        $this->assertResponseRedirects('/panier');
        $this->client->followRedirect();
        $this->assertStringContainsString('Votre panier est vide', $this->client->getResponse()->getContent());
    }

    #[Test]
    public function paymentPageShowsPrefilledCardFields(): void
    {
        $user = $this->createUser('buyer2@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Pomme', '2.50', $category);
        $this->createCartWithItems($user, [
            [$product, 2, '2.50'],
        ]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/commande/paiement');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('4242 4242 4242 4242', $content);
        $this->assertStringContainsString('12/28', $content);
        $this->assertStringContainsString('123', $content);
        $this->assertStringContainsString('Payer', $content);
    }

    #[Test]
    public function paymentProcessCreatesOrderAndRedirectsToConfirmation(): void
    {
        $user = $this->createUser('buyer3@test.com');
        $category = $this->createCategory('Légumes');
        $product1 = $this->createProduct('Carotte', '1.50', $category);
        $product2 = $this->createProduct('Tomate', '2.00', $category);
        $cart = $this->createCartWithItems($user, [
            [$product1, 3, '1.50'],
            [$product2, 2, '2.00'],
        ]);

        $this->client->loginUser($user);
        $this->client->request('POST', '/commande/paiement');

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Merci pour votre commande', $crawler->text());

        $this->entityManager->clear();
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Confirmed, $order->getStatus());

        $freshCart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($freshCart);
        $this->assertCount(0, $freshCart->getItems());
    }

    #[Test]
    public function accessConfirmationWithoutAuthenticationRedirectsToLogin(): void
    {
        $user = $this->createUser('buyer4@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Banane', '1.20', $category);
        $order = $this->createOrderWithItems($user, [
            [$product, 2, '1.20'],
        ]);

        $this->client->request('GET', '/commande/confirmation/' . $order->getId());

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function confirmationPageShowsOrderDetails(): void
    {
        $user = $this->createUser('buyer5@test.com');
        $category = $this->createCategory('Fruits');
        $product1 = $this->createProduct('Fraise', '4.00', $category);
        $product2 = $this->createProduct('Myrtille', '5.00', $category);
        $order = $this->createOrderWithItems($user, [
            [$product1, 2, '4.00'],
            [$product2, 1, '5.00'],
        ]);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/commande/confirmation/' . $order->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Merci pour votre commande', $crawler->text());
        $this->assertStringContainsString('#' . $order->getId(), $crawler->text());
        $this->assertStringContainsString('Fraise', $crawler->text());
        $this->assertStringContainsString('Myrtille', $crawler->text());
        $this->assertStringContainsString('Retour à l\'accueil', $crawler->text());
    }

    #[Test]
    public function accessOtherUserOrderReturns403(): void
    {
        $owner = $this->createUser('owner@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Kiwi', '3.00', $category);
        $order = $this->createOrderWithItems($owner, [
            [$product, 1, '3.00'],
        ]);

        $intruder = $this->createUser('intruder@test.com');
        $this->client->loginUser($intruder);
        $this->client->request('GET', '/commande/confirmation/' . $order->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function nonExistentOrderReturns404(): void
    {
        $user = $this->createUser('buyer6@test.com');

        $this->client->loginUser($user);
        $this->client->request('GET', '/commande/confirmation/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function confirmationPageHasLinkToHome(): void
    {
        $user = $this->createUser('buyer7@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Orange', '2.00', $category);
        $order = $this->createOrderWithItems($user, [
            [$product, 1, '2.00'],
        ]);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/commande/confirmation/' . $order->getId());

        $this->assertResponseIsSuccessful();
        $homeLink = $crawler->selectLink('Retour à l\'accueil');
        $this->assertCount(1, $homeLink);
    }

    #[Test]
    public function paymentProcessWithEmptyCartRedirectsToCart(): void
    {
        $user = $this->createUser('buyer8@test.com');

        $this->client->loginUser($user);
        $this->client->request('POST', '/commande/paiement');

        $this->assertResponseRedirects('/panier');
        $this->client->followRedirect();
        $this->assertStringContainsString('Votre panier est vide', $this->client->getResponse()->getContent());
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $user->setVerifiedAt(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCategory(string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($name);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $name, string $price, Category $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription($name);
        $product->setImage(strtolower($name) . '.jpg');
        $product->setPrice($price);
        $product->addCategory($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * @param array{array{0: Product, 1: int, 2: string}} $itemsData
     */
    private function createCartWithItems(User $user, array $itemsData): Cart
    {
        $cart = new Cart();
        $cart->setUser($user);
        $this->entityManager->persist($cart);

        foreach ($itemsData as $data) {
            $item = new CartItem();
            $item->setCart($cart);
            $item->setProduct($data[0]);
            $item->setQuantity($data[1]);
            $item->setPrice($data[2]);
            $cart->addItem($item);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        return $cart;
    }

    /**
     * @param array{array{0: Product, 1: int, 2: string}} $itemsData
     */
    private function createOrderWithItems(User $user, array $itemsData): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setStatus(OrderStatus::Confirmed);
        $this->entityManager->persist($order);

        foreach ($itemsData as $data) {
            $line = new OrderLine();
            $line->setOrder($order);
            $line->setProduct($data[0]);
            $line->setQuantity($data[1]);
            $line->setPrice($data[2]);
            $order->addOrderLine($line);
            $this->entityManager->persist($line);
        }

        $this->entityManager->flush();

        return $order;
    }
}
