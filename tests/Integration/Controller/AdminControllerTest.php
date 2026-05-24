<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine ol')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category ca')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function adminCanViewUserList(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/utilisateurs');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des utilisateurs');
    }

    #[Test]
    public function adminCanDeactivateUser(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);

        $target = new User();
        $target->setEmail('target@test.com');
        $target->setFirstName('Target');
        $target->setLastName('User');
        $target->setPassword($passwordHasher->hashPassword($target, 'password'));
        $target->setIsActive(true);
        $entityManager->persist($target);
        $entityManager->flush();

        $this->client->loginUser($admin);

        $this->client->request('POST', '/admin/utilisateur/' . $target->getId() . '/toggle', [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('toggle-user-' . $target->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/utilisateurs');

        $entityManager->clear();
        $deactivatedUser = $entityManager->find(User::class, $target->getId());
        $this->assertFalse($deactivatedUser->isActive());
    }

    #[Test]
    public function adminCannotDeactivateSelf(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $this->client->loginUser($admin);

        $this->client->request('POST', '/admin/utilisateur/' . $admin->getId() . '/toggle', [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('toggle-user-' . $admin->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/utilisateurs');

        $entityManager->clear();
        $reloadedAdmin = $entityManager->find(User::class, $admin->getId());
        $this->assertTrue($reloadedAdmin->isActive());
    }

    #[Test]
    public function adminCanViewDashboard(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard Administration');
    }

    #[Test]
    public function adminCanCreateCategory(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/categories/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'category_type[name]' => 'Nouvelle catégorie',
            'category_type[description]' => 'Description test',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/categories');

        $entityManager->clear();
        $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => 'Nouvelle catégorie']);
        $this->assertNotNull($category);
        $this->assertSame('Description test', $category->getDescription());
    }

    #[Test]
    public function adminCanEditCategory(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Ancien nom');

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/categories/' . $category->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'category_type[name]' => 'Nom modifié',
            'category_type[description]' => 'Description modifiée',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/categories');

        $entityManager->clear();
        $updated = $entityManager->find(Category::class, $category->getId());
        $this->assertSame('Nom modifié', $updated->getName());
        $this->assertSame('Description modifiée', $updated->getDescription());
    }

    #[Test]
    public function adminCanDeleteCategory(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Catégorie à supprimer');

        $this->client->loginUser($admin);
        $token = $this->client->getContainer()->get('security.csrf.token_manager')
            ->getToken('delete-category-' . $category->getId())->getValue();

        $this->client->request('POST', '/admin/categories/' . $category->getId() . '/delete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/admin/categories');

        $entityManager->clear();
        $deleted = $entityManager->find(Category::class, $category->getId());
        $this->assertNull($deleted);
    }

    #[Test]
    public function adminCanViewProductList(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Fruits');
        $this->createProduct($entityManager, 'Pomme', $category);

        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/produits');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des produits');
    }

    #[Test]
    public function adminCanCreateProduct(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Légumes');

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/produits/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'product_type[name]' => 'Carotte',
            'product_type[description]' => 'Carotte bio',
            'product_type[price]' => '2.50',
            'product_type[image]' => 'images/carotte.jpg',
            'product_type[categories]' => [$category->getId()],
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/produits');

        $entityManager->clear();
        $product = $entityManager->getRepository(Product::class)->findOneBy(['name' => 'Carotte']);
        $this->assertNotNull($product);
        $this->assertSame('2.5', $product->getPrice());
        $this->assertCount(1, $product->getCategories());
        $this->assertSame('Légumes', $product->getCategories()->first()->getName());
    }

    #[Test]
    public function adminCanEditProduct(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Fruits');
        $product = $this->createProduct($entityManager, 'Pomme', $category);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/produits/' . $product->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'product_type[name]' => 'Pomme Golden',
            'product_type[price]' => '3.00',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/produits');

        $entityManager->clear();
        $updated = $entityManager->find(Product::class, $product->getId());
        $this->assertSame('Pomme Golden', $updated->getName());
        $this->assertSame('3', $updated->getPrice());
    }

    #[Test]
    public function adminCanDeleteProduct(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Fruits');
        $product = $this->createProduct($entityManager, 'Produit à supprimer', $category);

        $this->client->loginUser($admin);
        $token = $this->client->getContainer()->get('security.csrf.token_manager')
            ->getToken('delete-product-' . $product->getId())->getValue();

        $this->client->request('POST', '/admin/produits/' . $product->getId() . '/delete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/admin/produits');

        $entityManager->clear();
        $deleted = $entityManager->find(Product::class, $product->getId());
        $this->assertNull($deleted);
    }

    #[Test]
    public function adminCannotDeleteProductInOrder(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = $this->createAdminUser($entityManager, $passwordHasher);
        $category = $this->createCategory($entityManager, 'Fruits');
        $product = $this->createProduct($entityManager, 'Produit commandé', $category);

        $order = new Order();
        $order->setUser($admin);
        $entityManager->persist($order);

        $orderLine = new OrderLine();
        $orderLine->setOrder($order);
        $orderLine->setProduct($product);
        $orderLine->setQuantity(1);
        $orderLine->setPrice('2.50');
        $entityManager->persist($orderLine);
        $entityManager->flush();

        $this->client->loginUser($admin);
        $token = $this->client->getContainer()->get('security.csrf.token_manager')
            ->getToken('delete-product-' . $product->getId())->getValue();

        $this->client->request('POST', '/admin/produits/' . $product->getId() . '/delete', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/admin/produits');

        $entityManager->clear();
        $stillExists = $entityManager->find(Product::class, $product->getId());
        $this->assertNotNull($stillExists);
    }

    #[Test]
    public function nonAdminCannotAccessAdmin(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('user@test.com');
        $user->setFirstName('Simple');
        $user->setLastName('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $user->setVerifiedAt(new DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/produits');

        $this->assertResponseStatusCodeSame(403);
    }

    private function createAdminUser(EntityManagerInterface $entityManager, mixed $passwordHasher): User
    {
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setVerifiedAt(new DateTimeImmutable());
        $entityManager->persist($admin);
        $entityManager->flush();

        return $admin;
    }

    private function createCategory(EntityManagerInterface $entityManager, string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription('Description de ' . $name);
        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    private function createProduct(EntityManagerInterface $entityManager, string $name, Category $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setDescription('Description de ' . $name);
        $product->setPrice('1.00');
        $product->setImage('images/' . $name . '.jpg');
        $product->addCategory($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }
}
