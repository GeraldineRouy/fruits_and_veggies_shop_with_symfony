<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\OrderLine;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Retourne les produits d'une catégorie paginés.
     *
     * @param Category $category La catégorie dont on veut les produits
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page (défaut: 12)
     * @return Paginator<int, Product>
     * @throws InvalidArgumentException si $page < 1
     */
    public function findByCategoryPaginated(
        Category $category,
        int $page = 1,
        int $limit = 12,
    ): Paginator {
        if ($page < 1) {
            throw new InvalidArgumentException('Page number must be at least 1.');
        }

        $query = $this->createCategoryQueryBuilder($category)
            ->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query);
    }

    /**
     * Retourne le QueryBuilder de base pour les requêtes liées aux catégories.
     */
    private function createCategoryQueryBuilder(Category $category): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.categories', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->orderBy('p.name', 'ASC');
    }

    /**
     * Retourne les N produits les plus commandés (basé sur la quantité totale dans OrderLine).
     *
     * @param int $limit Nombre de produits à retourner (>= 1)
     * @return Product[] Les produits triés du plus commandé au moins commandé
     * @throws InvalidArgumentException si $limit < 1
     */
    public function findTopMostOrdered(int $limit): array
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be at least 1.');
        }

        return $this->createQueryBuilder('p')
            ->select('p, SUM(ol.quantity) AS HIDDEN totalQty')
            ->join(OrderLine::class, 'ol', 'WITH', 'ol.product = p')
            ->groupBy('p.id')
            ->orderBy('totalQty', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
