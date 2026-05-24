# Tâche #003 - Story #009 : Interface d'administration des produits

## Objectif
Ajouter les routes, méthodes de contrôleur et templates pour le CRUD des produits dans l'AdminController.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Tâche #001 (ProductType), Tâche #002 (dashboard lien)
- Nécessaire pour : Tâche #005 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Ajouter dans `AdminController` les actions CRUD pour les produits, et créer les templates Twig associés.

**Routes à ajouter :**

| Méthode | Route | Nom | Description |
|---------|-------|-----|-------------|
| GET | `/admin/produits` | `app_admin_products` | Liste paginée des produits |
| GET | `/admin/produits/new` | `app_admin_product_new` | Formulaire de création |
| POST | `/admin/produits/new` | `app_admin_product_new` | Soumission création |
| GET | `/admin/produits/{id}/edit` | `app_admin_product_edit` | Formulaire d'édition |
| POST | `/admin/produits/{id}/edit` | `app_admin_product_edit` | Soumission modification |
| POST | `/admin/produits/{id}/delete` | `app_admin_product_delete` | Suppression avec confirmation |

**Cas nominaux :**
- L'admin voit une liste paginée des produits (nom, prix, image, catégories)
- L'admin peut créer un produit avec sélection de catégories (EntityType multiple)
- L'admin peut modifier un produit (pré-remplir catégories existantes)
- L'admin peut supprimer un produit

**Cas limites :**
- Produit avec image manquante : afficher un placeholder ou "pas d'image"
- Suppression d'un produit qui est dans des paniers/commandes → laisser faire Doctrine (contrainte FK, mais OrderLine utilise `product_id` donc il faut penser à la contrainte)
  - Si une OrderLine ou CartItem référence le produit, Doctrine lèvera une exception → flash erreur

**Gestion d'erreurs :**
- Données de formulaire invalides → réafficher le formulaire avec les erreurs
- Suppression impossible (produit référencé dans commandes/OrderLine) → Attraper l'exception Doctrine (contrainte FK), flash erreur "Ce produit ne peut pas être supprimé car il est référencé dans des commandes." et redirect
- Toute exception Doctrine → flash erreur générique et redirect

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/AdminController.php` | Modifier | Ajouter les actions produits |
| `templates/admin/products.html.twig` | Créer | Liste des produits |
| `templates/admin/product_form.html.twig` | Créer | Formulaire produit (création/édition) |

### Signatures

```php
#[Route('/produits', name: 'app_admin_products', methods: ['GET'])]
public function products(
    Request $request,
    ProductRepository $productRepository,
    PaginationService $paginationService,
): Response

#[Route('/produits/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
public function newProduct(Request $request, EntityManagerInterface $entityManager): Response

#[Route('/produits/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
public function editProduct(
    Request $request,
    Product $product,
    EntityManagerInterface $entityManager,
): Response

#[Route('/produits/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
public function deleteProduct(
    Request $request,
    Product $product,
    EntityManagerInterface $entityManager,
): Response
```

### Contraintes techniques
- **Framework** : Symfony Forms + Doctrine ORM
- **Pattern** : Suivre strictement le même style que les actions catégories (Tâche #002)
- **Pagination** : Utiliser `PaginationService::paginateQuery()` avec 12 éléments par page, tri alphabétique sur `p.name`
- **Requête DQL** : Pour la liste, faire un `leftJoin('p.categories', 'c')` avec `addSelect('c')` pour éviter les requêtes N+1
- **Image** : Afficher l'image via `<img src="{{ asset(product.image) }}" alt="{{ product.name }}">` (même pattern que `shop/products.html.twig`)
- **Prix** : Afficher via `product.price|format_currency('EUR')`
- **CSRF** : Token CSRF pour la suppression
- **Flash messages** : `success` pour création/modification/suppression, `error` pour les erreurs

### Templates

#### products.html.twig
- Titre "Gestion des produits"
- Lien "Nouveau produit" → `app_admin_product_new`
- Tableau : Image (miniature 50x50), Nom, Prix, Catégories, Actions (Modifier, Supprimer)
- Pagination (même style que `admin/categories.html.twig`)
- Message "Aucun produit" si liste vide

#### product_form.html.twig
- Formulaire pour créer/éditer un produit
- Affiche `form_start`, `form_widget`, `form_end`
- Pour le champ `categories` (EntityType multiple), utiliser `form_row` pour un affichage propre
- Bouton "Enregistrer" et lien "Annuler" → `app_admin_products`

### Tests à implémenter
- Les tests d'intégration sont dans la Tâche #005

### Documentation
- Documentée via la Tâche #006 (README)
