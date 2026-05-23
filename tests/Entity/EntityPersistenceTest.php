<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->entityManager->beginTransaction();
    }

    #[Test]
    public function canPersistUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('hashed');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());
        $this->assertGreaterThan(0, $user->getId());
    }

    #[Test]
    public function canPersistCategory(): void
    {
        $category = new Category();
        $category->setName('Fruits');
        $category->setDescription('Fruits frais de saison');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->assertNotNull($category->getId());
    }

    #[Test]
    public function canPersistProductWithCategory(): void
    {
        $category = new Category();
        $category->setName('Fruits');
        $category->setDescription('Fruits frais');
        $this->entityManager->persist($category);

        $product = new Product();
        $product->setName('Pomme Golden');
        $product->setDescription('Pomme golden delicious');
        $product->setImage('pomme-golden.jpg');
        $product->setPrice('2.50');
        $product->addCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertNotNull($product->getId());
        $this->assertCount(1, $product->getCategories());
    }

    #[Test]
    public function canPersistFullOrder(): void
    {
        $user = new User();
        $user->setEmail('client@example.com');
        $user->setFirstName('Jane');
        $user->setLastName('Doe');
        $user->setPassword('hashed');
        $this->entityManager->persist($user);

        $product = new Product();
        $product->setName('Pomme');
        $product->setDescription('Pomme bio');
        $product->setImage('pomme.jpg');
        $product->setPrice('1.50');
        $this->entityManager->persist($product);

        $order = new Order();
        $order->setUser($user);
        $this->entityManager->persist($order);

        $orderLine = new OrderLine();
        $order->addOrderLine($orderLine);
        $orderLine->setProduct($product);
        $orderLine->setQuantity(3);
        $orderLine->setPrice('1.50');
        $this->entityManager->persist($orderLine);

        $this->entityManager->flush();

        $this->assertNotNull($order->getId());
        $this->assertNotNull($orderLine->getId());
        $this->assertSame($user, $order->getUser());
        $this->assertCount(1, $order->getOrderLines());
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        $this->entityManager->close();
        parent::tearDown();
    }
}
