<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
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
    public function accessCartWithoutAuthenticationRedirectsToLogin(): void
    {
        $this->client->request('GET', '/panier');

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function cartPageShowsEmptyMessageForNewUser(): void
    {
        $user = $this->createUser('user@test.com');

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Votre panier est vide', $crawler->text());
    }

    #[Test]
    public function addProductToCart(): void
    {
        $user = $this->createUser('buyer@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Pomme', '2.50', $category);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/boutique/produit/' . $product->getId());
        $form = $crawler->filter('.add-to-cart-form')->form();
        $form['add_to_cart[quantity]'] = 2;
        $this->client->submit($form);

        $this->assertResponseRedirects('/panier');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function addProductIncrementsQuantity(): void
    {
        $user = $this->createUser('buyer2@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Pomme', '2.50', $category);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/boutique/produit/' . $product->getId());
        $form = $crawler->filter('.add-to-cart-form')->form();
        $form['add_to_cart[quantity]'] = 2;
        $this->client->submit($form);

        $crawler = $this->client->request('GET', '/boutique/produit/' . $product->getId());
        $form = $crawler->filter('.add-to-cart-form')->form();
        $form['add_to_cart[quantity]'] = 3;
        $this->client->submit($form);

        $this->assertResponseRedirects('/panier');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function cartPageShowsAddedProducts(): void
    {
        $user = $this->createUser('fullcart@test.com');
        $category = $this->createCategory('Légumes');
        $product1 = $this->createProduct('Carotte', '1.50', $category);
        $product2 = $this->createProduct('Tomate', '2.00', $category);

        $cart = $this->createCartWithItems($user, [
            [$product1, 3, '1.50'],
            [$product2, 2, '2.00'],
        ]);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Carotte', $crawler->text());
        $this->assertStringContainsString('Tomate', $crawler->text());
    }

    #[Test]
    public function updateCartItemQuantity(): void
    {
        $user = $this->createUser('updater@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Banane', '1.20', $category);

        $cart = $this->createCartWithItems($user, [
            [$product, 2, '1.20'],
        ]);

        $itemId = $cart->getItems()->first()->getId();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/panier');
        $form = $crawler->filter('.cart-quantity-form')->form();
        $form['update_cart_item[quantity]'] = 5;
        $this->client->submit($form);

        $this->assertResponseRedirects('/panier');

        $this->entityManager->clear();
        $updatedItem = $this->entityManager->getRepository(CartItem::class)->find($itemId);
        $this->assertNotNull($updatedItem);
        $this->assertSame(5, $updatedItem->getQuantity());
    }

    #[Test]
    public function removeCartItem(): void
    {
        $user = $this->createUser('remover@test.com');
        $category = $this->createCategory('Fruits');
        $product = $this->createProduct('Kiwi', '3.00', $category);

        $cart = $this->createCartWithItems($user, [
            [$product, 1, '3.00'],
        ]);

        $itemId = $cart->getItems()->first()->getId();

        $this->client->loginUser($user);
        $this->client->request('POST', '/panier/supprimer/' . $itemId);

        $this->assertResponseRedirects('/panier');
        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function clearCart(): void
    {
        $user = $this->createUser('clearer@test.com');
        $category = $this->createCategory('Fruits');
        $product1 = $this->createProduct('Fraise', '4.00', $category);
        $product2 = $this->createProduct('Myrtille', '5.00', $category);
        $product3 = $this->createProduct('Framboise', '6.00', $category);

        $cart = $this->createCartWithItems($user, [
            [$product1, 1, '4.00'],
            [$product2, 2, '5.00'],
            [$product3, 1, '6.00'],
        ]);

        $this->client->loginUser($user);
        $this->client->request('POST', '/panier/vider');

        $this->assertResponseRedirects('/panier');
        $this->entityManager->refresh($cart);
        $this->assertCount(0, $cart->getItems());
    }

    #[Test]
    public function addNonExistentProductReturns404(): void
    {
        $user = $this->createUser('missing@test.com');

        $this->client->loginUser($user);
        $this->client->request('POST', '/panier/ajouter/99999', [
            'add_to_cart' => ['quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(404);
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
}
