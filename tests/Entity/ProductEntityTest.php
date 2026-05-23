<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    #[Test]
    public function productGettersAndSetters(): void
    {
        $product = new Product();
        $product->setName('Pomme Golden');
        $product->setDescription('Pomme golden delicious');
        $product->setImage('pomme-golden.jpg');
        $product->setPrice('2.50');

        $this->assertNull($product->getId());
        $this->assertEquals('Pomme Golden', $product->getName());
        $this->assertEquals('Pomme golden delicious', $product->getDescription());
        $this->assertEquals('pomme-golden.jpg', $product->getImage());
        $this->assertEquals('2.50', $product->getPrice());
    }

    #[Test]
    public function productDefaultValues(): void
    {
        $product = new Product();
        $this->assertNull($product->getName());
        $this->assertNull($product->getDescription());
        $this->assertNull($product->getImage());
        $this->assertNull($product->getPrice());
        $this->assertCount(0, $product->getCategories());
    }

    #[Test]
    public function productAddAndRemoveCategory(): void
    {
        $product = new Product();
        $category = new Category();
        $category->setName('Fruits');
        $category->setDescription('Fruits frais');

        $product->addCategory($category);
        $this->assertCount(1, $product->getCategories());
        $this->assertCount(1, $category->getProducts());
        $this->assertSame($category, $product->getCategories()->first());
        $this->assertSame($product, $category->getProducts()->first());

        $product->removeCategory($category);
        $this->assertCount(0, $product->getCategories());
        $this->assertCount(0, $category->getProducts());
    }
}
