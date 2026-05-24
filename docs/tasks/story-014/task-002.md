# Tâche #002 - Story #014 : Affichage du nombre de produits par catégorie sur l'accueil

## Objectif
Ajouter une méthode dans `CategoryRepository` pour retourner les catégories avec le nombre de produits associés, modifier `HomeController` pour passer ces informations au template, et mettre à jour l'affichage des catégories pour montrer le compteur.

## Contexte
- Story #014 : `docs/stories/story-014.md`
- Dépend de : Tâche #001 (template réorganisé — modifier la boucle des catégories après la réorganisation)
- Nécessaire pour : Tâche #003 (tests)
- `CategoryRepository` existant : méthode `findAllOrdered()` retourne les catégories triées par nom
- `HomeController` existant : injecte `CategoryRepository` et passe `categories` au template
- `templates/home/index.html.twig` : affiche les catégories avec nom et description (modifié dans Tâche #001)
- Relation : `Category` ManyToMany vers `Product` (via `Category.products`)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Chaque catégorie sur la page d'accueil doit afficher son **nombre de produits associés** à côté du nom (ex: "Fruits (5)"). La donnée doit être calculée dynamiquement par une requête DQL.

**Cas nominaux :**
- Chaque catégorie affiche "(N)" où N est le nombre de produits associés
- Une catégorie avec 5 produits affiche "Fruits (5)"
- Une catégorie sans produit affiche "Légumes (0)"
- Le comptage inclut tous les produits, quelle que soit leur visibilité ou disponibilité

**Cas limites :**
- Catégorie sans aucun produit → affiche "(0)"
- Aucune catégorie en base → la section complète est masquée (géré dans Tâche #001)
- Très grand nombre de produits (ex: 999+) → affiche le nombre exact sans formatage spécial

**Gestion d'erreurs :**
- Si la requête DQL échoue → laisser l'exception Doctrine remonter (comportement standard)
- `CategoryRepository::findAllOrdered()` reste inchangé — ne pas le modifier

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Repository/CategoryRepository.php` | Modifier | Ajouter `findAllWithProductCount()` |
| `src/Controller/HomeController.php` | Modifier | Utiliser la nouvelle méthode au lieu de `findAllOrdered()` |
| `templates/home/index.html.twig` | Modifier | Ajouter l'affichage du compteur de produits |
| `tests/Unit/Repository/CategoryRepositoryTest.php` | Modifier | Ajouter les tests pour la nouvelle méthode |

### Signatures

```php
namespace App\Repository;

use App\Entity\Category;

class CategoryRepository extends ServiceEntityRepository
{
    /**
     * Retourne toutes les catégories triées par nom, avec le nombre de produits associés.
     *
     * Chaque élément du tableau retourné est un tableau associatif contenant :
     * - 'category' : l'objet Category
     * - 'productCount' : le nombre de produits associés (int)
     *
     * @return array<int, array{category: Category, productCount: int}>
     */
    public function findAllWithProductCount(): array;
}
```

### Requête DQL attendue

```sql
SELECT c, COUNT(p.id) AS HIDDEN productCount
FROM App\Entity\Category c
LEFT JOIN c.products p
GROUP BY c.id
ORDER BY c.name ASC
```

Utiliser `->getResult()` avec un hydratage qui retourne les données structurées. Comme Doctrine ne supporte pas nativement le mapping en tableau associatif avec des objets via DQL, procéder ainsi :

```php
public function findAllWithProductCount(): array
{
    $results = $this->createQueryBuilder('c')
        ->select('c, COUNT(p.id) AS HIDDEN productCount')
        ->leftJoin('c.products', 'p')
        ->groupBy('c.id')
        ->orderBy('c.name', 'ASC')
        ->getQuery()
        ->getResult();

    // $results est un tableau mixte contenant l'objet Category et le scalar productCount
    // Format Doctrine : [['categoryObject', 'productCount' => int], ...]
    $categories = [];
    foreach ($results as $result) {
        $categories[] = [
            'category' => $result[0],
            'productCount' => (int) $result['productCount'],
        ];
    }

    return $categories;
}
```

### Modification du HomeController

Dans `src/Controller/HomeController.php`, remplacer l'appel à `findAllOrdered()` par `findAllWithProductCount()` :

```php
public function index(): Response
{
    return $this->render('home/index.html.twig', [
        'categories' => $this->categoryRepository->findAllWithProductCount(),
    ]);
}
```

**Important :** La variable `categories` change de type : ce n'est plus un tableau d'objets `Category` mais un tableau de `array{category: Category, productCount: int}`. Le template doit être adapté.

### Modification du template

Dans `templates/home/index.html.twig` (déjà modifié dans Tâche #001), la boucle `{% for category in categories %}` doit être adaptée pour accéder aux nouvelles propriétés :

```twig
{% for item in categories %}
    {% set category = item.category %}
    {% set productCount = item.productCount %}
    <a href="{{ path('app_shop_category', { id: category.id }) }}" class="category-card ...">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ category.name }} <span class="text-brand-600 text-sm font-normal">({{ productCount }})</span></h3>
        <p class="text-gray-600 text-sm">{{ category.description }}</p>
    </a>
{% endfor %}
```

Ou plus simplement avec l'opérateur `.` de Twig :

```twig
{% for item in categories %}
    <a href="{{ path('app_shop_category', { id: item.category.id }) }}" class="category-card ...">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ item.category.name }} <span class="text-brand-600 text-sm font-normal">({{ item.productCount }})</span></h3>
        <p class="text-gray-600 text-sm">{{ item.category.description }}</p>
    </a>
{% endfor %}
```

Le compteur est affiché en `text-brand-600 text-sm font-normal` pour le distinguer visuellement du nom de la catégorie.

### Contraintes techniques
- **Framework** : Symfony 8.0, Doctrine ORM 3, PHP 8.4
- **Requête** : Utiliser `LEFT JOIN` pour inclure les catégories sans produit (elles doivent afficher 0). Utiliser `GROUP BY c.id` pour l'agrégation.
- **Ordre** : Les catégories sont triées par `c.name ASC` (ordre alphabétique). L'ordre est inchangé par rapport à `findAllOrdered()`.
- **Type de retour** : Le retour est un tableau de tableaux associatifs (pas d'objets DTO dédiés — on reste simple). Pas de classe DTO à créer.
- **Performance** : Une seule requête SQL avec jointure LEFT et COUNT, pas de requête N+1. Le LEFT JOIN + COUNT est optimal.
- **Ne pas modifier** : `findAllOrdered()` doit rester inchangé pour ne pas casser les usages existants (dashboard admin).
- **Style** : Respecter PSR-12, `declare(strict_types=1)`, PHPDoc avec `@param` et `@return`

### Tests à implémenter

**Fichier** : `tests/Unit/Repository/CategoryRepositoryTest.php`

Ajouter les tests dans le fichier existant (créé par Story #004, Tâche #003).

#### Tests unitaires

- Scénario 1 : `testFindAllWithProductCountReturnsCategoriesWithCounts()`
  - Données : Créer 2 catégories : "Fruits" avec 3 produits, "Légumes" avec 0 produit
  - Résultat attendu : 2 éléments, "Fruits" a `productCount` = 3, "Légumes" a `productCount` = 0
  - Vérifier : l'ordre alphabétique est respecté

- Scénario 2 : `testFindAllWithProductCountReturnsEmptyArrayWhenNoCategories()`
  - Données : Aucune catégorie en base
  - Résultat attendu : `[]` (tableau vide)

- Scénario 3 : `testFindAllOrderedRemainsUnchanged()`
  - Données : Créer 2 catégories
  - Résultat attendu : `findAllOrdered()` retourne toujours un tableau d'objets `Category` (régression)

### Documentation

Aucune documentation spécifique pour cette tâche.

### Exemples d'utilisation

```twig
{# Dans le template home/index.html.twig #}
{% for item in categories %}
    {{ item.category.name }} ({{ item.productCount }})
{% endfor %}
```
