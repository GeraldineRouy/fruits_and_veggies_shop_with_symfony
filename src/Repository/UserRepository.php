<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findOneByEmailVerificationToken(string $token): ?User
    {
        return $this->findOneBy(['emailVerificationToken' => $token]);
    }

    public function createPaginatedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');
    }

    /**
     * @return User[]
     */
    public function findInactiveSince(DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLoginAt IS NOT NULL')
            ->andWhere('u.lastLoginAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUnverifiedSince(DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.verifiedAt IS NULL')
            ->andWhere('u.createdAt IS NOT NULL')
            ->andWhere('u.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }
}
