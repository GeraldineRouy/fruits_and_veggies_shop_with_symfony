<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderEntityTest extends TestCase
{
    #[Test]
    public function orderGettersAndSetters(): void
    {
        $user = new User();
        $order = new Order();
        $order->setUser($user);

        $this->assertNull($order->getId());
        $this->assertSame($user, $order->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getOrderedAt());
        $this->assertEquals(OrderStatus::Confirmed, $order->getStatus());
    }

    #[Test]
    public function orderDefaultStatus(): void
    {
        $order = new Order();
        $this->assertEquals(OrderStatus::Confirmed, $order->getStatus());
    }

    #[Test]
    public function orderStatusTransition(): void
    {
        $order = new Order();
        $order->setStatus(OrderStatus::Shipped);
        $this->assertEquals(OrderStatus::Shipped, $order->getStatus());

        $order->setStatus(OrderStatus::Delivered);
        $this->assertEquals(OrderStatus::Delivered, $order->getStatus());

        $order->setStatus(OrderStatus::Cancelled);
        $this->assertEquals(OrderStatus::Cancelled, $order->getStatus());
    }

    #[Test]
    public function orderAddAndRemoveOrderLine(): void
    {
        $order = new Order();
        $product = new Product();
        $orderLine = new OrderLine();
        $orderLine->setProduct($product);
        $orderLine->setQuantity(2);
        $orderLine->setPrice('5.00');

        $order->addOrderLine($orderLine);
        $this->assertCount(1, $order->getOrderLines());
        $this->assertSame($order, $orderLine->getOrder());

        $order->removeOrderLine($orderLine);
        $this->assertCount(0, $order->getOrderLines());
        $this->assertNull($orderLine->getOrder());
    }
}
