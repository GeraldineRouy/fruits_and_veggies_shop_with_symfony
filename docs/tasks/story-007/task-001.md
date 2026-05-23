# Tâche #001 - Story #007 : ProductRepository::findTopMostOrdered

## Objectif

Ajouter une méthode `findTopMostOrdered(int $limit)` au `ProductRepository` qui retourne les produits les plus commandés, basée sur la somme des quantités dans `OrderLine`.

## Contexte

- Story #007 : `docs/stories/story-007.md`
- Dépend de : Story #002 (entités Product, OrderLine), Story #006 (OrderService, commandes en base)
- Nécessaire pour : Tâche #002 (contrôleur imbriqué)
- Entités existantes : `Product`, `OrderLine`, `Order` (déjà mappées)
- Repositories existants : `ProductRepository`, `OrderLineRepository`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Ajouter une méthode DQL qui calcule le top N produits les plus commandés.

**Cas nominaux :**
- Retourne exactement `$limit` produits quand assez de commandes existent
- Retourne moins de `$limit` produits si le nombre total de produits commandés est inférieur
- Les produits sans aucune commande ne sont pas inclus dans le résultat
- L'ordre est décroissant : le produit avec la plus grande quantité totale commandée en premier

**Cas limites :**
- Aucune commande en base → tableau vide
- Moins de produits commandés que `$limit` → retourne tous les produits commandés
- Un même produit commandé plusieurs fois → agrégation correcte des quantités

**Gestion d'erreurs :**
- `$limit < 1` → `InvalidArgumentException('Limit must be at least 1.')`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Repository/ProductRepository.php` | Modifier | Ajouter la méthode `findTopMostOrdered` |
| `tests/Unit/Repository/ProductRepositoryTest.php` | Modifier | Ajouter les tests unitaires |

### Signatures

```php
namespace App\Repository;

use App\Entity\Product;

class ProductRepository extends ServiceEntityRepository
{
    /**
     * Retourne les N produits les plus commandés (basé sur la quantité totale dans OrderLine).
     *
     * @param int $limit Nombre de produits à retourner (>= 1)
     * @return Product[] Les produits triés du plus commandé au moins commandé
     * @throws InvalidArgumentException si $limit < 1
     */
    public function findTopMostOrdered(int $limit): array;
}
```

### Requête DQL attendue

```sql
SELECT p, SUM(ol.quantity) AS HIDDEN totalQty
FROM App\Entity\OrderLine ol
JOIN ol.product p
GROUP BY p.id
ORDER BY totalQty DESC
```

Limiter le résultat avec `->setMaxResults($limit)`.

### Contraintes techniques

- **Framework** : Symfony 8.0 / Doctrine ORM 3
- **Pattern** : Suivre le pattern existant des méthodes du `ProductRepository` — `createQueryBuilder()`, DQL, query methods. Respecter le style des méthodes existantes (`findByCategoryPaginated`)
- **PHP** : 8.4 — promoted properties, type hints stricts
- **Entités** : Utiliser les getters existants de `Product` et `OrderLine`
- **La méthode doit être testable** : pouvoir insérer des données de test, exécuter la requête, et vérifier l'ordre des résultats
- **Requête** : Utiliser DQL avec `GROUP BY p.id` et `ORDER BY SUM(ol.quantity) DESC`. Utiliser `->getResult()` pour retourner un tableau d'objets `Product`

### Tests à implémenter

Ajouter les tests DANS le fichier existant `tests/Unit/Repository/ProductRepositoryTest.php`.

Le `setUp()` du fichier existant nettoie déjà les entités OrderLine, Order, Product, Category. Tu peux réutiliser les méthodes helpers privées déjà présentes (`createCategory`, `createProducts`).

Il faut aussi créer des Order et OrderLine pour pouvoir tester les quantités commandées. Ajoute une méthode privée `createOrderWithProduct(Product $product, int $quantity): void`.

#### Tests unitaires

**Fichier** : `tests/Unit/Repository/ProductRepositoryTest.php`

- Scénario 1 : Top 3 retourne les 3 produits les plus commandés dans l'ordre
  - Données : 4 produits (P1 qty=10, P2 qty=5, P3 qty=3, P4 qty=0)
  - Résultat attendu : [P1, P2, P3] dans cet ordre

- Scénario 2 : Limit = 1 ne retourne que le meilleur produit
  - Données : 3 produits (P1 qty=10, P2 qty=5, P3 qty=3)
  - Résultat attendu : [P1] (1 élément)

- Scénario 3 : Moins de produits commandés que le limit
  - Données : 2 produits commandés, limit = 5
  - Résultat attendu : [P1, P2] (2 éléments)

- Scénario 4 : Aucune commande → tableau vide
  - Données : 3 produits, aucune OrderLine
  - Résultat attendu : []

- Scénario 5 : Agrégation correcte des quantités (même produit commandé plusieurs fois)
  - Données : P1 commandé 2 fois (qty=3 + qty=7), P2 commandé 1 fois (qty=5)
  - Résultat attendu : [P1, P2] (P1 total = 10, P2 total = 5)

- Scénario 6 : Exception pour limit < 1
  - Données : limit = 0
  - Résultat attendu : `InvalidArgumentException`

### Documentation

Aucune documentation spécifique pour cette tâche.

### Exemples d'utilisation

```php
// Dans un contrôleur (tâche #002) :
$topProducts = $productRepository->findTopMostOrdered(3);

// $topProducts est un array<Product> : [Pomme, Banane, Orange]
```
