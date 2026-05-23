# Tâche #001 - Story #004 : Repository layer — pagination et recherche par catégorie

## Objectif
Ajouter les méthodes de requêtage nécessaires aux repositories `ProductRepository` et `CategoryRepository` pour le catalogue : pagination des produits par catégorie, et liste ordonnée des catégories.

## Contexte
- Story #004 : [Catalogue produits et navigation](../../stories/story-004.md)
- Dépend de : Story #002 (entités Product, Category déjà créées, repositories existants basiques)
- Nécessaire pour : Tâche #002 (ShopController + templates), Tâche #003 (Tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Les repositories `ProductRepository` et `CategoryRepository` existent mais ne contiennent que le constructeur hérité. Il faut ajouter les méthodes métier nécessaires à l'affichage du catalogue.

**Cas nominaux :**
- `ProductRepository::findByCategoryPaginated()` retourne les produits d'une catégorie donnée, triés par nom, avec pagination (12 par page par défaut)
- `ProductRepository::findByCategoryPaginated()` retourne un `Doctrine\ORM\Tools\Pagination\Paginator` compatible avec un itérateur Twig
- `CategoryRepository::findAllOrdered()` retourne toutes les catégories triées par nom (ordre alphabétique)

**Cas limites :**
- Page demandée = 1 → retourne les 12 premiers produits
- Page demandée > nombre total de pages → retourne une collection vide (pas d'erreur)
- Catégorie sans produit → Paginator vide, pas d'erreur
- Page < 1 → lever `\InvalidArgumentException('Page must be greater than 0')`

**Gestion d'erreurs :**
- Page < 1 → `\InvalidArgumentException('Page number must be at least 1.')`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Repository/ProductRepository.php` | Modifier | Ajouter `findByCategoryPaginated()` |
| `src/Repository/CategoryRepository.php` | Modifier | Ajouter `findAllOrdered()` |

### Signatures

```php
namespace App\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

class ProductRepository extends ServiceEntityRepository
{
    /**
     * Retourne les produits d'une catégorie paginés.
     *
     * @param Category $category La catégorie dont on veut les produits
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page (défaut: 12)
     * @return Paginator<int, Product>
     * @throws \InvalidArgumentException si $page < 1
     */
    public function findByCategoryPaginated(
        Category $category,
        int $page = 1,
        int $limit = 12
    ): Paginator;

    /**
     * Retourne le QueryBuilder de base pour les requêtes liées aux catégories.
     */
    private function createCategoryQueryBuilder(Category $category): QueryBuilder;
}
```

```php
namespace App\Repository;

class CategoryRepository extends ServiceEntityRepository
{
    /**
     * Retourne toutes les catégories triées par nom (ordre alphabétique).
     *
     * @return Category[]
     */
    public function findAllOrdered(): array;
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0, Doctrine ORM 3, PHP 8.4
- **Pagination** : Utiliser `Doctrine\ORM\Tools\Pagination\Paginator` (déjà disponible dans le bundle Doctrine, pas de package supplémentaire nécessaire). Le `Paginator` retourné sera consommé par `PaginationService::paginate()` (créé dans la Tâche #002).
- **Requête** : Utiliser le QueryBuilder avec jointure sur `p.categories` pour filtrer par catégorie. Trier les produits par `p.name` (ordre alphabétique ASC).
- **Style** : Respecter le code existant (PSR-12, `declare(strict_types=1)`, type hints, PHPDoc avec `@param` et `@return`)
- **Imports** : Ajouter les imports nécessaires :
  - `Doctrine\ORM\QueryBuilder`
  - `Doctrine\ORM\Tools\Pagination\Paginator`
  - `App\Entity\Category`
- **Performance** : `Paginator` utilise `fetchJoinCollection=true` par défaut (nécessaire pour les jointures ManyToMany). Ne pas forcer `false` sauf test de performance contraignant.
- **Migration** : Aucune migration nécessaire (pas de changement de schéma)

### Tests à implémenter

Les tests de cette tâche sont écrits dans la Tâche #003. Cependant, le code doit être écrit de manière à être testable (méthodes publiques avec signatures claires).

### Documentation

Aucune documentation spécifique pour cette tâche (documentation dans la Tâche #003).
