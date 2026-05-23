<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CategoryEntityTest extends TestCase
{
    #[Test]
    public function categoryGettersAndSetters(): void
    {
        $category = new Category();
        $category->setName('Fruits');
        $category->setDescription('Fruits frais de saison');

        $this->assertNull($category->getId());
        $this->assertEquals('Fruits', $category->getName());
        $this->assertEquals('Fruits frais de saison', $category->getDescription());
    }

    #[Test]
    public function categoryDefaultValues(): void
    {
        $category = new Category();
        $this->assertNull($category->getName());
        $this->assertNull($category->getDescription());
        $this->assertCount(0, $category->getProducts());
    }
}
