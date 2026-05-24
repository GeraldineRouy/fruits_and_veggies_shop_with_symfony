# Tâche #002 - Story #009 : Interface d'administration des catégories

## Objectif
Ajouter les routes, méthodes de contrôleur et templates pour le CRUD des catégories dans l'AdminController.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Tâche #001 (CategoryType)
- Nécessaire pour : Tâche #005 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Ajouter dans `AdminController` les actions CRUD pour les catégories, et créer les templates Twig associés.

**Routes à ajouter :**

| Méthode | Route | Nom | Description |
|---------|-------|-----|-------------|
| GET | `/admin` | `app_admin_dashboard` | Page d'accueil du dashboard admin |
| GET | `/admin/categories` | `app_admin_categories` | Liste paginée des catégories |
| GET | `/admin/categories/new` | `app_admin_category_new` | Formulaire de création |
| POST | `/admin/categories/new` | `app_admin_category_new` | Soumission création |
| GET | `/admin/categories/{id}/edit` | `app_admin_category_edit` | Formulaire d'édition |
| POST | `/admin/categories/{id}/edit` | `app_admin_category_edit` | Soumission modification |
| POST | `/admin/categories/{id}/delete` | `app_admin_category_delete` | Suppression |

**Cas nominaux :**
- L'admin voit une liste paginée des catégories avec nom et description
- L'admin peut créer une nouvelle catégorie via un formulaire
- L'admin peut modifier une catégorie existante
- L'admin peut supprimer une catégorie (avec confirmation)
- L'admin a une page dashboard `/admin` avec des liens vers la gestion des commandes, utilisateurs, catégories et produits

**Cas limites :**
- Suppression d'une catégorie qui a des produits associés (ManyToMany) : la suppression est autorisée, les liaisons sont automatiquement nettoyées par Doctrine
- Page 0 ou négative : rediriger vers page 1
- Catégorie inexistante : laisser Symfony générer une 404 automatiquement via le ParamConverter

**Gestion d'erreurs :**
- Données de formulaire invalides → réafficher le formulaire avec les erreurs
- Exception Doctrine en suppression → flash erreur et redirect

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/AdminController.php` | Modifier | Ajouter les actions categories + dashboard |
| `src/Repository/CategoryRepository.php` | Modifier | Ajouter `createPaginatedQueryBuilder()` |
| `templates/admin/dashboard.html.twig` | Créer | Dashboard admin |
| `templates/admin/categories.html.twig` | Créer | Liste des catégories |
| `templates/admin/category_form.html.twig` | Créer | Formulaire catégorie (création/édition) |

### Signatures

```php
// AdminController
#[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
public function dashboard(): Response

#[Route('/categories', name: 'app_admin_categories', methods: ['GET'])]
public function categories(
    Request $request,
    CategoryRepository $categoryRepository,
    PaginationService $paginationService,
): Response

#[Route('/categories/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
public function newCategory(Request $request, EntityManagerInterface $entityManager): Response

#[Route('/categories/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
public function editCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response

#[Route('/categories/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
```

### Contraintes techniques
- **Framework** : Symfony Forms + Doctrine ORM
- **Pattern** : Suivre le style existant de `AdminController` (utilisation d'attributs Symfony, injection de dépendances dans les méthodes)
- **Pagination** : Utiliser `PaginationService::paginateQuery()` avec 20 éléments par page, tri alphabétique sur `c.name`
- **CategoryRepository** : Ajouter une méthode `createPaginatedQueryBuilder(): QueryBuilder` qui retourne un QueryBuilder trié par `c.name` ASC (pour la pagination). Cette méthode est dans `src/Repository/CategoryRepository.php`.
- **CSRF** : Le formulaire de suppression utilise un token CSRF
- **Flash messages** : `success` pour création/modification/suppression, `error` pour les erreurs
- **Layout** : Tous les templates admin étendent `base.html.twig`
- **Navigation** : Ne PAS ajouter de lien "Administration" dans `base.html.twig`. L'accès au dashboard se fait uniquement via l'URL directe `/admin` ou un marque-page. Pas de modification de `base.html.twig`.

### Templates

#### dashboard.html.twig
- Titre "Dashboard Administration"
- 4 cartes/liens : Commandes, Utilisateurs, Catégories, Produits
- Chaque lien pointe vers la route correspondante

#### categories.html.twig
- Titre "Gestion des catégories"
- Lien "Nouvelle catégorie" → `app_admin_category_new`
- Tableau : Nom, Description, Actions (Modifier, Supprimer)
- Pagination (comme dans `admin/users.html.twig`)
- Message "Aucune catégorie" si vide

#### category_form.html.twig
- Formulaire pour créer/éditer une catégorie
- Affiche `form_start`, `form_widget`, `form_end`
- Bouton "Enregistrer" et lien "Annuler" → `app_admin_categories`

### Tests à implémenter
- Les tests d'intégration sont dans la Tâche #005 (ne pas dupliquer)

### Documentation
- Documentée via la Tâche #006 (README)
