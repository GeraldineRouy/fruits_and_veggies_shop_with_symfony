# Tâche #005 - Story #009 : Tests automatisés

## Objectif
Implémenter les tests unitaires et d'intégration pour les fonctionnalités d'administration des produits et catégories.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Tâche #001, Tâche #002, Tâche #003
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer les tests unitaires (formulaires), d'intégration (CRUD), et mettre à jour le test existant d'intégration admin.

**Critères de test :**
- Validation des formulaires CategoryType et ProductType
- CRUD complet d'une catégorie
- CRUD complet d'un produit
- Accès sécurisé (admin requis)
- Gestion d'erreurs (suppression produit lié à des commandes)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Unit/Form/CategoryTypeTest.php` | Créer | Tests unitaires du formulaire CategoryType |
| `tests/Unit/Form/ProductTypeTest.php` | Créer | Tests unitaires du formulaire ProductType |
| `tests/Integration/Controller/AdminControllerTest.php` | Modifier | Ajouter tests CRUD catégories et produits |

### Tests à implémenter

#### Tests unitaires Formulaire

**Fichier :** `tests/Unit/Form/CategoryTypeTest.php`
- Utiliser `KernelTestCase` pour accéder au `form.factory`
- Scénario 1 : Soumission valide → données mappées sur Category
- Scénario 2 : Name vide → formulaire invalide

**Fichier :** `tests/Unit/Form/ProductTypeTest.php`
- Utiliser `KernelTestCase`
- Scénario 1 : Soumission valide avec catégories
- Scénario 2 : Price négatif → formulaire invalide
- Scénario 3 : Nom trop long > 255 → formulaire invalide

#### Tests d'intégration Controller

**Fichier :** `tests/Integration/Controller/AdminControllerTest.php` (à modifier)
Ajouter après les tests existants :

Scénario 1 : `adminCanViewDashboard`
- Admin connecté accède à GET `/admin`
- Vérifie `h1` contient "Dashboard"

Scénario 2 : `adminCanCreateCategory`
- Admin connecté accède à GET `/admin/categories/new`
- Soumet le formulaire avec name + description valides
- Vérifie redirection vers `/admin/categories` et flash success
- Vérifie que la catégorie est persistée en base

Scénario 3 : `adminCanEditCategory`
- Créer une catégorie, puis POST sur `/admin/categories/{id}/edit`
- Vérifie la modification en base

Scénario 4 : `adminCanDeleteCategory`
- Créer une catégorie, puis POST sur `/admin/categories/{id}/delete`
- Vérifie suppression en base

Scénario 5 : `adminCanViewProductList`
- Créer une catégorie et un produit
- GET `/admin/produits` → succès

Scénario 6 : `adminCanCreateProduct`
- Créer une catégorie au préalable
- Soumet le formulaire produit avec catégories sélectionnées
- Vérifie persistance et association ManyToMany

Scénario 7 : `adminCanEditProduct`
- Créer produit + catégorie, édition du produit
- Vérifie mise à jour en base

Scénario 8 : `adminCanDeleteProduct`
- Créer un produit non référencé
- Supprimer → vérifie suppression

Scénario 9 : `adminCannotDeleteProductInOrder`
- Créer un produit, l'associer à une commande (OrderLine)
- Tenter de supprimer → flash error

Scénario 10 : `nonAdminCannotAccessAdmin`
- Créer un utilisateur sans ROLE_ADMIN
- Tenter d'accéder à `/admin/produits` → 403

### Contraintes techniques
- **Framework** : PHPUnit 13, Symfony WebTestCase/KernelTestCase
- **Style** : Suivre exactement les patterns existants dans `AdminControllerTest.php` :
  - Classe `final`
  - Attribut `#[Test]` sur chaque méthode
  - Helper methods privées en bas du fichier pour créer des entités de test
  - Nettoyage DQL en `setUp()` pour les entités concernées (Product, Category, OrderLine, CartItem, Cart, Order)
  - Injection via `$this->client->loginUser($admin)` pour l'authentification
- **Helper methods à créer** : `createAdminUser(): User`, `createCategory(string $name): Category`, `createProduct(string $name, Category $category): Product`
- **Pas de fixtures** : Tout créer en mémoire dans les tests

### Documentation
- Aucune documentation spécifique
