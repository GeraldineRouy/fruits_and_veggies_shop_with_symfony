<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne toutes les catégories triées par nom (ordre alphabétique).
     *
     * @return Category[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les catégories triées par nom avec le nombre de produits associés.
     *
     * @return array<int, array{category: Category, productCount: int}>
     */
    public function findAllWithProductCount(): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c, COUNT(p.id) AS productCount')
            ->leftJoin('c.products', 'p')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $categories = [];
        foreach ($results as $result) {
            $categories[] = [
                'category' => $result[0],
                'productCount' => (int) $result['productCount'],
            ];
        }

        return $categories;
    }

    /**
     * Retourne un QueryBuilder pour la pagination des catégories.
     */
    public function createPaginatedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');
    }
}
