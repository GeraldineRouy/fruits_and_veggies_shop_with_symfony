<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderLineEntityTest extends TestCase
{
    #[Test]
    public function orderLineGettersAndSetters(): void
    {
        $orderLine = new OrderLine();
        $orderLine->setQuantity(3);
        $orderLine->setPrice('1.50');

        $this->assertNull($orderLine->getId());
        $this->assertEquals(3, $orderLine->getQuantity());
        $this->assertEquals('1.50', $orderLine->getPrice());
    }

    #[Test]
    public function orderLineNullByDefault(): void
    {
        $orderLine = new OrderLine();
        $this->assertNull($orderLine->getId());
        $this->assertNull($orderLine->getQuantity());
        $this->assertNull($orderLine->getPrice());
        $this->assertNull($orderLine->getOrder());
        $this->assertNull($orderLine->getProduct());
    }

    #[Test]
    public function orderLineWithRelations(): void
    {
        $order = new Order();
        $product = new Product();
        $product->setName('Pomme');
        $product->setDescription('Pomme bio');
        $product->setImage('pomme.jpg');
        $product->setPrice('1.50');

        $orderLine = new OrderLine();
        $orderLine->setOrder($order);
        $orderLine->setProduct($product);
        $orderLine->setQuantity(5);
        $orderLine->setPrice('1.50');

        $this->assertSame($order, $orderLine->getOrder());
        $this->assertSame($product, $orderLine->getProduct());
        $this->assertEquals(5, $orderLine->getQuantity());
    }
}
