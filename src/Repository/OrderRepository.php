<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Enum\OrderStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function findStalledOrders(DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status NOT IN (:excluded)')
            ->setParameter('excluded', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])
            ->andWhere('o.orderedAt < :before')
            ->setParameter('before', $before)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
