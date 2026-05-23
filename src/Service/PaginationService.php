<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationService
{
    /**
     * Calcule les métadonnées de pagination pour un Paginator.
     *
     * @param Paginator $paginator Le Paginator Doctrine (déjà configuré avec setFirstResult/setMaxResults)
     * @param int $page Numéro de la page courante (1-indexed)
     * @param int $limit Nombre d'éléments par page
     * @return array{
     *     items: iterable,
     *     totalItems: int,
     *     totalPages: int,
     *     currentPage: int,
     *     limit: int,
     *     hasPrevious: bool,
     *     hasNext: bool
     * }
     */
    public function paginate(
        Paginator $paginator,
        int $page,
        int $limit = 12,
    ): array {
        $totalItems = count($paginator);
        $totalPages = max(1, (int) ceil($totalItems / $limit));

        return [
            'items' => $paginator,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
            'hasPrevious' => $page > 1,
            'hasNext' => $page < $totalPages,
        ];
    }
}
