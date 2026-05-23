# Spécification - Fruits & Veggies Shop

## Vue d'ensemble
Site web e-commerce de vente de fruits et légumes frais destiné au public francophone de la région grenobloise. Projet pédagogique Symfony (MVC) avec gestion de catalogue, panier, commandes et utilisateurs.

## Stack technique
- **Framework :** Symfony 7.4
- **Base de données :** MySQL (LTS)
- **PHP :** 8.x
- **Frontend :** Twig + AssetMapper
- **Docker :** Container PHP 8.x + container MySQL LTS
- **Versioning :** Git avec dépôt distant

## Entités

### User
- `email` (string)
- `firstName` (string)
- `lastName` (string)
- `roles` (json)
- `password` (string)
- `verifiedAt` (datetime, nullable) — date de validation du compte
- `lastLoginAt` (datetime, nullable) — date de dernière connexion
- `isActive` (boolean) — désactivation par admin

### Category
- `name` (string, non null)
- `description` (string, non null)

### Product
- `name` (string, non null)
- `description` (text, non null)
- `image` (string, non null)
- `price` (float, non null)
- `categories` (relation ManyToMany vers Category)

### Order
- `user` (relation ManyToOne vers User)
- `orderedAt` (datetime, non null)
- `status` (string, non null) — valeurs : `confirmed`, `preparing`, `shipped`, `delivered`, `cancelled`

### OrderLine
- `order` (relation ManyToOne vers Order)
- `quantity` (integer, non null)
- `price` (float, non null)
- `product` (relation ManyToOne vers Product)

## Relations
- Product ⟷ Category : ManyToMany (un produit peut avoir plusieurs catégories, une catégorie peut avoir plusieurs produits)
- Order → User : ManyToOne (une commande a un utilisateur)
- Order → OrderLine : OneToMany (une commande peut avoir plusieurs lignes)
- OrderLine → Product : ManyToOne (une ligne de commande a un seul produit)

## Controllers
1. **HomeController** - page d'accueil avec les 3 produits les plus commandés (contrôleur imbriqué)
2. **ShopController** - catalogue produits
3. **CartController** - gestion du panier
4. **SecurityController** - inscription avec validation email, connexion, mot de passe oublié
5. **UserController** - profil utilisateur
6. **AdminController** - backoffice pour désactiver/supprimer des utilisateurs et gérer les comptes inactifs

## Services

### MailerService
- Email par changement de statut de commande (de la confirmation à la livraison)
- Email création de compte, modification mot de passe, MDP oublié, suppression de compte
- Solution locale simple (Mailpit en dev)

### CartService
Basé sur SessionInterface / RequestStack, dépend de MailService
- `getItems()`
- `getTotal()`
- `getProductCount()`
- `addProduct()`
- `removeOneProduct()`
- `removeProduct()`
- `clearCart()`
- `cartToOrder()`

## Commandes (Console)
1. Supprimer utilisateurs inactifs depuis 2 ans (basé sur `lastLoginAt`)
2. Supprimer utilisateurs n'ayant pas validé leur compte depuis 7 jours (basé sur `verifiedAt`)
3. Lister commandes envoyées depuis +7 jours non livrées

## Fonctionnalités supplémentaires
- Page d'accueil avec les 3 produits les plus commandés (contrôleur imbriqué obligatoire)
- Admin peut désactiver (pas supprimer) un compte utilisateur (champ `isActive`)
- Event listener déconnectant un utilisateur désactivé

## Sécurité
- Authentification : email / mot de passe
- Rôles : ROLE_USER, ROLE_ADMIN
- Inscription avec validation par email

## Contraintes Docker
- Container PHP 8.x
- Container MySQL LTS
- Migrations Symfony exécutées automatiquement au démarrage du container PHP
- Configuration docker-compose à créer

## Inconsistances résolues
- Symfony 7.4 retenu (ni 5.2 ni 8.0)
- MySQL LTS retenu (pas PostgreSQL)
- Entité renommée **OrderLine** (pas OrderItem)
- Relation Product → Category : ManyToMany confirmée
