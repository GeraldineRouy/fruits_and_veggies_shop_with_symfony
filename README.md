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

## Documentation

- [Environnement Docker](docs/docker-compose.md)
- [Spécification projet](docs/specification/specification.md)
- [Stories](docs/stories/)
- [Schéma de données](docs/schema-er.md)
- [Authentification](docs/security/login.md)
- [Réinitialisation de mot de passe](docs/security/password-reset.md)

## Page d'accueil et top produits

La page d'accueil affiche les 3 produits les plus commandés via un **contrôleur imbriqué** (Embedded Controller).

### Fonctionnement

- `App\Controller\TopProductsController::topProducts()` interroge `ProductRepository::findTopMostOrdered(3)`
- La requête DQL agrège les quantités des `OrderLine` pour déterminer les produits les plus populaires
- Le contrôleur n'a pas de route dédiée : il est appelé uniquement via `render(controller(...))` dans `templates/home/index.html.twig`
- En l'absence de commandes, la section "Top produits" est masquée

### Contrôleur imbriqué

```twig
{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```

Ce pattern Symfony permet de déléguer le rendu d'un bloc à un contrôleur dédié, isolant la logique métier du template principal.

## Stack

- **PHP** : 8.4 (FrankenPHP, ZTS)
- **Framework** : Symfony 8.0
- **Base de données** : PostgreSQL 16
- **Frontend** : Twig + Stimulus + Turbo + AssetMapper
- **Serveur web** : FrankenPHP (Caddy)
- **Messaging** : Symfony Messenger (Doctrine transport)
- **Email** : Symfony Mailer (Mailpit en dev)

## Email

L'environnement Docker utilise **Mailpit** pour les emails en développement.

- SMTP : `mailer:1025` (configuré dans `compose.yaml`)
- Web UI : accessible via le port dynamique (`docker compose port mailer 8025`)

Les emails transactionnels sont envoyés via `App\Service\MailerService` :
- Validation de compte après inscription
- Réinitialisation de mot de passe

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

## Commandes console

| Commande | Description |
|---|---|
| `bin/console app:users:purge-inactive` | Supprime les comptes inactifs depuis plus de 2 ans |
| `bin/console app:users:purge-inactive --dry-run` | Simule la suppression sans modifier la base |
| `bin/console app:users:purge-unverified` | Supprime les comptes non validés après 7 jours |
| `bin/console app:users:purge-unverified --dry-run` | Simule la suppression sans modifier la base |
| `bin/console app:orders:list-stalled` | Liste les commandes non livrées depuis plus de 7 jours |

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
