# Tâche #002 - Story #011 : Tests automatisés pour les données enrichies

## Objectif
Implémenter les tests unitaires et d'intégration qui vérifient que les données produits enrichies (descriptions avec unités d'achat, nouvelle catégorie, nouveaux produits, top 3) sont correctes.

## Contexte
- Story #011 : `docs/stories/story-011.md`
- Dépend de : Tâche #001 (migration d'enrichissement)
- Nécessaire pour : Tâche #003 (documentation)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### Test unitaire : descriptions des produits contiennent l'unité d'achat
Créer un test unitaire qui vérifie que chaque produit a une description contenant l'unité d'achat appropriée.

**Cas nominaux :**
- Avocat → description contient "à la pièce"
- Mangue → description contient "à la pièce"
- Ananas → description contient "à la pièce"
- Fraise → description contient "barquette de 250g"
- Basilic → description contient "au bouquet" (ne contient PAS "en pot")
- Menthe → description contient "au bouquet" (ne contient PAS "en pot")
- Persil → description contient "au bouquet" (ne contient PAS "en botte")
- Pomme Golden → description contient "au kilogramme"
- Banane → description contient "au kilogramme"
- Orange → description contient "au kilogramme"
- Carotte → description contient "au kilogramme"
- Salade → description contient "au kilogramme"
- Tomate → description contient "au kilogramme"
- Concombre → description contient "au kilogramme"
- Courgette → description contient "au kilogramme"

**Cas nominaux (nouveaux produits) :**
- Noix de Grenoble AOC → description contient "au kilogramme"
- Huile de noix de Grenoble AOC → description contient "à la bouteille"
- Bleu du Vercors-Sassenage → description contient "à la pièce"
- Saint-Marcellin → description contient "à la pièce"
- Chocolat Bonnat → description contient "à la pièce"

#### Test d'intégration : catégorie "Produits locaux d'exception"
Vérifier que la catégorie "Produits locaux d'exception" contient exactement les 5 nouveaux produits.

**Cas nominaux :**
- La catégorie "Produits locaux d'exception" existe
- Elle contient les 5 produits régionaux
- Les noms des produits correspondent exactement

**Cas limites :**
- La catégorie "Légumes bio" n'existe plus
- Les produits qui étaient aussi dans "Légumes" existent toujours

#### Test d'intégration : top 3 des produits
Vérifier que la requête `findTopMostOrdered(3)` retourne fraises, Saint-Marcellin, ananas dans cet ordre.

**Cas nominaux :**
- Le premier résultat est "Fraise"
- Le deuxième résultat est "Fromage Saint-Marcellin"
- Le troisième résultat est "Ananas"

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Integration/EnrichedProductDataTest.php` | Créer | Tests d'intégration pour catégorie et top 3 |
| `tests/Unit/ProductDescriptionUnitTest.php` | Créer | Test unitaire pour les descriptions avec unités |

### Signatures

```php
namespace App\Tests\Integration;

#[Test]
public function categoryProduitsLocauxExceptionContainsFiveProducts(): void
{
    // Récupère la catégorie "Produits locaux d'exception"
    // Vérifie qu'elle contient 5 produits
    // Vérifie les noms des produits
}

#[Test]
public function categoryLegumesBioNoLongerExists(): void
{
    // Vérifie que la catégorie "Légumes bio" n'existe pas
}

#[Test]
public function productsThatWereInLegumesBioStillExist(): void
{
    // Vérifie que les produits (carotte, tomate, courgette) existent toujours
}

#[Test]
public function topThreeProductsAreFraiseSaintMarcellinAnanas(): void
{
    // Appelle findTopMostOrdered(3)
    // Vérifie les noms et l'ordre
}
```

```php
namespace App\Tests\Unit;

#[Test]
public function productDescriptionsContainCorrectUnit(): void
{
    // Pour chaque produit du seed data, vérifie que la description
    // contient la chaîne d'unité attendue
}
```

### Contraintes techniques
- **Framework** : PHPUnit 13 avec attributs `#[Test]` (pas d'annotation `@test`)
- **Style** : Suivre le style des tests existants (ex: `ProductRepositoryTest.php`, `AdminMigrationFlowTest.php`)
- **Base de données** : Utiliser un `KernelTestCase` pour les tests d'intégration (nécessite une vraie connexion DB)
- **Cleanup** : Nettoyer les données entre les tests (`setUp()` doit supprimer les données)
- **Fixtures** : Ne pas créer de fixtures séparées — les tests d'intégration insèrent leurs propres données via l'ORM
- **Données de test** : Utiliser l'ORM (persist/flush) comme dans `ProductRepositoryTest` pour créer les catégories, produits et OrderLines nécessaires aux tests

### Tests à implémenter

#### Tests unitaires
- **Fichier** : `tests/Unit/ProductDescriptionUnitTest.php`
- Scénario 1 (unité complète) : Créer des produits via l'ORM avec les nouvelles descriptions enrichies, puis vérifier que chaque description contient l'une des chaînes d'unité d'achat ("à la pièce", "au bouquet", "barquette de 250g", "au kilogramme", "à la bouteille")
- Scénario 2 (unité spécifique) : Vérifier produit par produit que l'unité correcte est associée

#### Tests d'intégration
- **Fichier** : `tests/Integration/EnrichedProductDataTest.php`
- Scénario 1 : Créer la catégorie "Produits locaux d'exception" et ses 5 produits via l'ORM, puis vérifier leur existence et leurs noms
- Scénario 2 : Vérifier la suppression de "Légumes bio" (catégorie non trouvée)
- Scénario 3 : Créer des OrderLines via l'ORM pour simuler le top 3 (fraises, Saint-Marcellin, ananas), puis appeler `findTopMostOrdered(3)` et vérifier l'ordre
- Scénario 4 : Vérifier que les produits qui étaient dans "Légumes bio" et "Légumes" existent toujours
