# Fruits & Veggies Shop

Boutique en ligne de fruits et légumes frais.

## Prérequis

- Docker & Docker Compose
- Git

## Installation

```bash
# Cloner le projet
git clone <url-du-repo>
cd fruits_and_veggies_shop

# Démarrer l'environnement
docker compose up -d --build

# Exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

La page d'accueil est accessible sur [http://localhost:8000](http://localhost:8000).

## Démarrage rapide (après installation)

```bash
docker compose up -d
```

## Compte administrateur par défaut

Après installation et exécution des migrations, un compte administrateur est automatiquement créé :

| Champ | Valeur |
|-------|--------|
| Email | `admin@example.com` |
| Mot de passe | `admin` |

Ce compte possède le rôle `ROLE_ADMIN` et permet d'accéder au dashboard d'administration sur `/admin`.

> ⚠️ **Recommandation de sécurité** : Après votre première connexion, changez le mot de passe de ce compte. Ne conservez pas le mot de passe par défaut en production.

## Tests

```bash
docker compose exec app php bin/phpunit
```

## Commandes utiles

| Commande | Description |
|---|---|
| `docker compose up -d` | Démarrer les services |
| `docker compose down` | Arrêter les services |
| `docker compose logs -f app` | Voir les logs PHP |
| `docker compose exec app php bin/console ...` | Exécuter une commande Symfony |
| `docker compose exec app php bin/phpunit` | Exécuter les tests |
| `docker compose build app` | Reconstruire l'image PHP |

## Images et assets

### Structure des dossiers

```
public/assets/images/
├── home/
│   └── welcome.png          # Image de bienvenue page d'accueil
├── products/
│   ├── pommes.png           # Images des produits (PNG)
│   ├── bananes.png
│   └── ...
└── avatars/
    ├── avatar_user.png      # Avatar utilisateur connecté
    └── avatar_admin.png     # Avatar administrateur
```

### Gestion des images produits

- Les images des produits sont stockées dans `public/assets/images/products/` au format PNG
- Le champ `image` de l'entité `Product` contient le chemin relatif (ex: `assets/images/products/pommes.png`)
- La fonction Twig `{{ asset() }}` résout le chemin depuis le dossier `public/`
- Pour les produits sans image associée, un message "Image non disponible" est affiché
- Les images sont chargées avec `loading="lazy"` pour optimiser les performances

## Documentation

- [Environnement Docker](docs/docker-compose.md)
- [Spécification projet](docs/specification/specification.md)
- [Stories](docs/stories/)
- [Schéma de données](docs/schema-er.md)
- [Authentification](docs/security/login.md)
- [Réinitialisation de mot de passe](docs/security/password-reset.md)

## Page d'accueil

La page d'accueil est organisée en trois sections dans cet ordre :

1. **Texte de bienvenue** : Message de présentation de la boutique
2. **Top 3 produits** : Les 3 produits les plus commandés (via contrôleur imbriqué)
3. **Catégories** : Liste des catégories avec le nombre de produits associés

### Texte de bienvenue

```
Bienvenue chez Fruits & Veggies Shop, votre primeur et épicerie fine grenobloise !
Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception.
```

### Top produits

Les 3 produits les plus commandés sont affichés via un **contrôleur imbriqué** (Embedded Controller).

- `App\Controller\TopProductsController::topProducts()` interroge `ProductRepository::findTopMostOrdered(3)`
- La requête DQL agrège les quantités des `OrderLine` pour déterminer les produits les plus populaires
- Le contrôleur n'a pas de route dédiée : il est appelé uniquement via `render(controller(...))` dans `templates/home/index.html.twig`
- En l'absence de commandes, la section "Top produits" est masquée

```twig
{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```

### Catégories

Les catégories de produits sont listées avec le nombre de produits associés :

- Chaque catégorie affiche son nom, sa description et le nombre de produits (ex: "Fruits (5)")
- Les catégories sont triées par ordre alphabétique
- Le clic sur une catégorie redirige vers la liste de ses produits (`/boutique/{id}`)
- En l'absence de catégories, un message informatif est affiché

## Stack

- **PHP** : 8.4 (FrankenPHP, ZTS)
- **Framework** : Symfony 8.0
- **Base de données** : PostgreSQL 16
- **Frontend** : Twig + Stimulus + Turbo + AssetMapper
- **Serveur web** : FrankenPHP (Caddy)
- **Messaging** : Symfony Messenger (Doctrine transport)
- **Email** : Symfony Mailer (Mailpit en dev)

## Style CSS avec Tailwind

Le projet utilise **Tailwind CSS** pour le style, intégré via CDN.

### Intégration

Tailwind CSS est chargé via CDN dans `templates/base.html.twig` :

```html
<script src="https://cdn.tailwindcss.com"></script>
```

### Configuration

La configuration Tailwind est définie inline dans le layout :

```html
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#e8f5e9',
                        100: '#c8e6c9',
                        200: '#a5d6a7',
                        300: '#81c784',
                        400: '#66bb6a',
                        500: '#4caf50',
                        600: '#43a047',
                        700: '#388e3c',
                        800: '#2e7d32',
                        900: '#1b5e20',
                    }
                }
            }
        }
    }
</script>
```

### Palette de couleurs

| Token | Usage |
|-------|-------|
| `brand-600` | Boutons principaux, prix, liens |
| `brand-700` | Header, footer |
| `brand-800` | Footer copyright |
| `brand-200` | Texte secondaire sur fond sombre |

### Composants stylisés

Les composants suivants utilisent exclusivement des classes utilitaires Tailwind :

- **Header/Navbar** : fond `bg-brand-700`, menu responsive avec hamburger
- **Footer** : 3 colonnes (à propos, liens, contact), copyright
- **Cartes produits** : `bg-white rounded-xl shadow-md hover:shadow-lg`
- **Cartes catégories** : `bg-white rounded-xl shadow-md p-6`
- **Pages d'auth** : carte centrée `max-w-md mx-auto`
- **Tableaux** : `bg-white rounded-xl shadow-md overflow-hidden`
- **Badges de statut** : couleurs conditionnelles (yellow/blue/purple/green/red)
- **Messages flash** : fonds colorés avec bordure
- **Aperçu panier** : dropdown `bg-white rounded-xl shadow-xl` au survol de l'icône panier

## Email

L'environnement Docker utilise **Mailpit** pour les emails en développement.

- SMTP : `mailer:1025` (configuré dans `compose.yaml`)
- Web UI : accessible via le port dynamique (`docker compose port mailer 8025`)

Les emails transactionnels sont envoyés via `App\Service\MailerService` :
- Validation de compte après inscription
- Réinitialisation de mot de passe

### Accéder à l'interface Mailpit

Pour consulter les emails reçus (validation de compte, confirmation de commande, etc.) :

```bash
# Récupérer l'URL d'accès (port dynamique)
docker compose port mailer 8025
```

Puis ouvrir l'URL affichée (ex: `http://localhost:8025`) dans le navigateur.

Ou, si le port 8025 est libre et mappé dans `compose.yaml` :
[http://localhost:8025](http://localhost:8025)

> 💡 Après une inscription sur `/register`, l'email de validation apparaît immédiatement dans Mailpit. Cliquez sur le lien de validation dans l'email pour activer le compte.

> ⚠️ Il se peut que le port mappé soit occupé par un autre service. Dans ce cas, utilisez la commande `docker compose port mailer 8025` pour obtenir le port dynamique affecté.

## Panier d'achat

Le panier est une fonctionnalité réservée aux utilisateurs connectés.

### Routes

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/panier` | Afficher le contenu du panier |
| POST | `/panier/ajouter/{id}` | Ajouter un produit (id = Product) |
| POST | `/panier/modifier/{id}` | Modifier la quantité (id = CartItem) |
| POST | `/panier/supprimer/{id}` | Supprimer un produit (id = CartItem) |
| POST | `/panier/vider` | Vider le panier |

### Service

`App\Service\CartService` — Documentation complète dans le code source.

Méthodes principales :
- `getOrCreateCart(User)` : Récupère ou crée le panier d'un utilisateur
- `addProduct(User, Product, int $quantity = 1)` : Ajoute un produit
- `updateItemQuantity(CartItem, int $quantity)` : Modifie la quantité (0 = supprime)
- `removeItem(CartItem)` : Supprime un item du panier
- `clearCart(User)` : Vide le panier
- `getTotal(User) : string` : Calcule le total
- `getProductCount(User) : int` : Compte les articles

## Administration des utilisateurs

L'interface d'administration permet aux administrateurs de gérer les comptes utilisateurs.

### Routes

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin/utilisateurs` | Liste paginée des utilisateurs |
| POST | `/admin/utilisateur/{id}/toggle` | Désactiver/réactiver un compte |

### Comportement

- Un administrateur peut désactiver ou réactiver n'importe quel utilisateur sauf lui-même
- Un utilisateur désactivé est automatiquement déconnecté lors de sa prochaine requête
- Un utilisateur désactivé ne peut pas se reconnecter

### Dashboard administration

Le dashboard admin (`/admin`) est accessible aux utilisateurs avec le rôle `ROLE_ADMIN`. Il centralise l'accès à toutes les fonctionnalités d'administration.

Routes du dashboard :

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin` | Dashboard principal (liens vers toutes les sections) |

### Gestion des catégories

L'admin peut gérer les catégories de produits via les routes suivantes, avec pagination (20 par page) :

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin/categories` | Liste paginée des catégories |
| GET | `/admin/categories/new` | Formulaire de création |
| POST | `/admin/categories/new` | Création |
| GET | `/admin/categories/{id}/edit` | Formulaire d'édition |
| POST | `/admin/categories/{id}/edit` | Modification |
| POST | `/admin/categories/{id}/delete` | Suppression |

### Gestion des produits

L'admin peut gérer les produits via les routes suivantes, avec pagination (12 par page) :

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin/produits` | Liste paginée des produits |
| GET | `/admin/produits/new` | Formulaire de création |
| POST | `/admin/produits/new` | Création |
| GET | `/admin/produits/{id}/edit` | Formulaire d'édition |
| POST | `/admin/produits/{id}/edit` | Modification |
| POST | `/admin/produits/{id}/delete` | Suppression |

Les formulaires de produit incluent la sélection multiple des catégories (relation ManyToMany).

### Données d'exemple

Une migration Doctrine insère des données d'exemple (fruits, légumes, produits régionaux) :

```bash
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

#### Catégories

| Nom | Description |
|-----|-------------|
| Fruits | Fruits frais de saison |
| Légumes | Légumes frais de saison |
| Fruits exotiques | Fruits tropicaux et exotiques |
| Herbes aromatiques | Herbes et plantes aromatiques |
| Produits locaux d'exception | Produits régionaux d'exception de nos terroirs |

#### Produits

| Nom | Unité d'achat | Catégories |
|-----|---------------|------------|
| Pomme Golden | au kilogramme | Fruits |
| Banane | au kilogramme | Fruits, Fruits exotiques |
| Orange | au kilogramme | Fruits |
| Fraise | barquette de 250g | Fruits |
| Avocat | à la pièce | Fruits exotiques, Légumes |
| Mangue | à la pièce | Fruits exotiques |
| Carotte | au kilogramme | Légumes |
| Salade | au kilogramme | Légumes |
| Tomate | au kilogramme | Légumes |
| Concombre | au kilogramme | Légumes |
| Courgette | au kilogramme | Légumes |
| Basilic | au bouquet | Herbes aromatiques |
| Menthe | au bouquet | Herbes aromatiques |
| Persil | au bouquet | Herbes aromatiques |
| Ananas | à la pièce | Fruits exotiques |
| Noix de Grenoble AOC | au kilogramme | Produits locaux d'exception |
| Huile de noix de Grenoble AOC | à la bouteille | Produits locaux d'exception |
| Fromage Bleu du Vercors-Sassenage | à la pièce | Produits locaux d'exception |
| Fromage Saint-Marcellin | à la pièce | Produits locaux d'exception |
| Chocolat Bonnat | à la pièce | Produits locaux d'exception |

#### Unités d'achat

Les produits sont vendus selon différentes unités d'achat, précisées dans leur description :

| Unité | Exemples de produits |
|-------|---------------------|
| À la pièce | Avocat, Mangue, Ananas, Saint-Marcellin, Bleu du Vercors, Chocolat Bonnat |
| Au bouquet | Basilic, Menthe, Persil |
| En barquette de 250g | Fraise |
| Au kilogramme | Pomme Golden, Banane, Orange, Carotte, Tomate, Noix de Grenoble |
| À la bouteille | Huile de noix de Grenoble AOC |

## Commandes console

| Commande | Description |
|---|---|
| `bin/console app:users:purge-inactive` | Supprime les comptes inactifs depuis plus de 2 ans |
| `bin/console app:users:purge-inactive --dry-run` | Simule la suppression sans modifier la base |
| `bin/console app:users:purge-unverified` | Supprime les comptes non validés après 7 jours |
| `bin/console app:users:purge-unverified --dry-run` | Simule la suppression sans modifier la base |
| `bin/console app:orders:list-stalled` | Liste les commandes non livrées depuis plus de 7 jours |

## Tests manuels

### Purge des utilisateurs inactifs (2 ans)

1. Édite le fichier `test-update-last-login.sql` à la racine du projet en remplaçant l'email :
```sql
UPDATE "user" SET last_login_at = '2023-01-01 00:00:00' WHERE email = 'user@example.com';
```

2. Exécute la mise à jour :
```bash
php bin/console dbal:run-sql "$(cat test-update-last-login.sql)"
```

3. Vérifie que l'utilisateur est détecté (dry-run) :
```bash
php bin/console app:users:purge-inactive --dry-run
```

4. Procède à la suppression :
```bash
php bin/console app:users:purge-inactive
```

### Purge des utilisateurs non vérifiés (7 jours)

1. Édite le fichier `test-update-unverified.sql` à la racine du projet en remplaçant l'email :
```sql
UPDATE "user" SET verified_at = NULL, created_at = '2026-01-15 00:00:00' WHERE email = 'user@example.com';
```

2. Exécute la mise à jour :
```bash
php bin/console dbal:run-sql "$(cat test-update-unverified.sql)"
```

3. Vérifie que l'utilisateur est détecté (dry-run) :
```bash
php bin/console app:users:purge-unverified --dry-run
```

4. Procède à la suppression :
```bash
php bin/console app:users:purge-unverified
```

### Commandes bloquées (non livrées après 7 jours)

1. Trouve une commande existante :
```bash
php bin/console dbal:run-sql "SELECT id, status, ordered_at FROM ""order"" LIMIT 5"
```

2. Édite le fichier `test-update-stalled-order.sql` en remplaçant l'ID et le statut (valeurs : `confirmed`, `preparing`, `shipped`) :
```sql
UPDATE "order" SET status = 'shipped', ordered_at = '2026-01-15 00:00:00' WHERE id = 1;
```

3. Exécute la mise à jour :
```bash
php bin/console dbal:run-sql "$(cat test-update-stalled-order.sql)"
```

4. Vérifie la liste :
```bash
php bin/console app:orders:list-stalled
```

> Les fichiers SQL de référence se trouvent à la racine du projet : `test-update-last-login.sql`, `test-update-unverified.sql`, `test-update-stalled-order.sql`.

## Inscription et connexion

| Route | Description |
|---|---|
| `/register` | Inscription (email, prénom, nom, mot de passe) |
| `/register/check-email` | Page post-inscription "Vérifiez vos emails" |
| `/verify-email?token=` | Validation de l'email via lien |
| `/login` | Connexion |
| `/logout` | Déconnexion |

### Configuration

| Variable | Défaut | Description |
|---|---|---|
| `MAILER_DSN` | `smtp://mailer:1025` (Docker) / `null://null` (local) | DSN du serveur SMTP |
| `MAILER_SENDER_EMAIL` | `noreply@fruits-veggies.local` | Adresse expéditeur |
| `APP_BASE_URL` | `http://localhost:8000` | URL publique pour les liens dans les emails |
