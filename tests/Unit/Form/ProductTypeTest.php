<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->formFactory = $container->get('form.factory');
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category ca')->execute();
    }

    private function createForm(mixed $data = null): \Symfony\Component\Form\FormInterface
    {
        return $this->formFactory->create(ProductType::class, $data, [
            'csrf_protection' => false,
        ]);
    }

    #[Test]
    public function validSubmissionWithCategories(): void
    {
        $category = $this->createCategory('Fruits');

        $product = new Product();
        $form = $this->createForm($product);

        $form->submit([
            'name' => 'Pomme',
            'description' => 'Pomme rouge',
            'price' => '2.50',
            'image' => 'images/pomme.jpg',
            'categories' => [$category->getId()],
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('Pomme', $product->getName());
        $this->assertSame('Pomme rouge', $product->getDescription());
        $this->assertSame('2.5', $product->getPrice());
        $this->assertSame('images/pomme.jpg', $product->getImage());
        $this->assertCount(1, $product->getCategories());
    }

    #[Test]
    public function negativePriceShowsError(): void
    {
        $product = new Product();
        $form = $this->createForm($product);

        $form->submit([
            'name' => 'Test',
            'description' => 'Test',
            'price' => '-5',
            'image' => 'test.jpg',
            'categories' => [],
        ]);

        $this->assertFalse($form->isValid());
    }

    #[Test]
    public function blankNameShowsError(): void
    {
        $product = new Product();
        $form = $this->createForm($product);

        $form->submit([
            'name' => '',
            'description' => 'Test',
            'price' => '2.50',
            'image' => 'test.jpg',
            'categories' => [],
        ]);

        $this->assertFalse($form->isValid());
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
}
