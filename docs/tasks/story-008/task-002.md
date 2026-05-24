# Tâche #002 - Story #008 : Commandes console de maintenance

## Objectif

Ajouter un champ `createdAt` à l'entité `User` et créer 3 commandes Symfony Console pour la maintenance des comptes utilisateurs et le suivi des commandes.

## Contexte

- Story #008 : `docs/stories/story-008.md`
- Dépend de : Story #002 (entités User, Order). **Prérequis :** Ajout du champ `createdAt` sur l'entité `User` (défini dans cette tâche)
- Nécessaire pour : Tâche #004 (tests)
- Exécution : Cette tâche doit être exécutée **après** la Tâche #001 (AdminController) et la Tâche #003 (event listener)
- Aucune commande console n'existe encore dans le projet
- Répertoire `src/Command/` vide ou inexistant
- `UserRepository` possède `findOneByEmail` et `findOneByEmailVerificationToken`
- `OrderRepository` est vide (aucune méthode personnalisée)
- Les entités `User` (`isActive`, `lastLoginAt`, `verifiedAt`) et `Order` (`orderedAt`, `status`) existent avec tous les champs nécessaires
- L'énumération `OrderStatus` existe dans `src/Enum/OrderStatus.php` avec les cas : `Confirmed`, `Preparing`, `Shipped`, `Delivered`, `Cancelled`
- Décision : Ajout du champ `createdAt` (datetime_immutable) sur `User` avec `options: ['default' => 'CURRENT_TIMESTAMP']`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Étape préalable : Ajout du champ `createdAt` sur User

Avant d'implémenter les commandes, ajouter le champ `createdAt` à l'entité `User` :

```php
#[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
private ?DateTimeInterface $createdAt = null;
```

Ajouter le getter et setter :

```php
public function getCreatedAt(): ?DateTimeInterface
{
    return $this->createdAt;
}

public function setCreatedAt(?DateTimeInterface $createdAt): self
{
    $this->createdAt = $createdAt;

    return $this;
}
```

Mettre à jour le constructeur pour initialiser `$this->createdAt = new DateTimeImmutable()`.

**Générer une migration** via `bin/console make:migration` puis l'exécuter.

**Pour les données existantes :** La migration doit définir `createdAt = NOW()` pour les enregistrements existants qui auront `NULL` (le défaut `CURRENT_TIMESTAMP` ne s'applique qu'aux nouvelles insertions). Ajouter manuellement dans la migration générée :

```php
$this->addSql('UPDATE "user" SET created_at = NOW() WHERE created_at IS NULL');
```

**Cas nominaux :**
- Un utilisateur créé via `new User()` a automatiquement `createdAt` initialisé
- Les anciens utilisateurs reçoivent `createdAt = NOW()` via la migration de données

### Description fonctionnelle

#### Commande 1 : `app:users:purge-inactive`

Supprime définitivement les comptes utilisateurs qui n'ont pas été connectés depuis plus de 2 ans.

**Cas nominaux :**
- Utilisateurs avec `lastLoginAt` < date courante - 2 ans → supprimés
- Utilisateurs avec `lastLoginAt` = null (jamais connecté) → **exclus** (conservateur, ne pas supprimer)

**Affichage :**
- Affiche le nombre d'utilisateurs supprimés
- Mode dry-run (`--dry-run`) : liste les utilisateurs qui seraient supprimés sans les supprimer

#### Commande 2 : `app:users:purge-unverified`

Supprime définitivement les comptes utilisateurs qui n'ont pas validé leur email après 7 jours.

**Cas nominaux :**
- Utilisateurs avec `verifiedAt` = null ET `createdAt` < date courante - 7 jours → supprimés
- Utilisateurs avec `createdAt` = null (migration non passée) → exclus
- Mode dry-run (`--dry-run`) : liste les utilisateurs qui seraient supprimés sans les supprimer

#### Commande 3 : `app:orders:list-stalled`

Liste les commandes dont le statut n'est pas `delivered` ou `cancelled` et dont la date `orderedAt` est antérieure à date courante - 7 jours.

**Cas nominaux :**
- Affiche un tableau avec : ID commande, email client, date commande, statut actuel, nombre de jours depuis la commande
- Les commandes livrées ou annulées sont exclues
- Tri par date de commande décroissante

**Affichage :**
- Tableau formaté dans la console
- Si aucune commande en attente → message "Aucune commande en attente de livraison."

**Gestion d'erreurs :**
- Si le statut `OrderStatus` n'existe pas → `InvalidArgumentException`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/User.php` | Modifier | Ajouter le champ `createdAt` (datetime_immutable) + getter/setter + init constructeur |
| `migrations/` | Créer | Générer une migration Doctrine pour le nouveau champ + migration de données |
| `src/Command/PurgeInactiveUsersCommand.php` | Créer | Commande `app:users:purge-inactive` |
| `src/Command/PurgeUnverifiedUsersCommand.php` | Créer | Commande `app:users:purge-unverified` |
| `src/Command/ListStalledOrdersCommand.php` | Créer | Commande `app:orders:list-stalled` |
| `src/Repository/UserRepository.php` | Modifier | Ajouter `findInactiveSince` et `findUnverifiedSince` |
| `src/Repository/OrderRepository.php` | Modifier | Ajouter `findStalledOrders` |

### Signatures

#### Commandes

```php
// src/Command/PurgeInactiveUsersCommand.php
// name: app:users:purge-inactive
// description: Supprime les comptes inactifs depuis plus de 2 ans
// arguments: none
// options:
//   --dry-run (bool) : Simule la suppression sans modifier la base

// src/Command/PurgeUnverifiedUsersCommand.php
// name: app:users:purge-unverified
// description: Supprime les comptes non validés après 7 jours (basé sur createdAt)
// arguments: none
// options:
//   --dry-run (bool) : Simule la suppression sans modifier la base

// src/Command/ListStalledOrdersCommand.php
// name: app:orders:list-stalled
// description: Liste les commandes non livrées depuis plus de 7 jours
// arguments: none
// options: none
```

#### Méthodes Repository

```php
// UserRepository

/**
 * @param \DateTimeImmutable $before Date limite
 * @return User[] Utilisateurs dont lastLoginAt < before (jamais connectés = exclus)
 */
public function findInactiveSince(\DateTimeImmutable $before): array;

/**
 * @param \DateTimeImmutable $before Date limite
 * @return User[] Utilisateurs non validés (verifiedAt = null) créés avant $before
 */
public function findUnverifiedSince(\DateTimeImmutable $before): array;
```

```php
// OrderRepository

/**
 * @param \DateTimeImmutable $before Date limite
 * @return Order[] Commandes non livrées et non annulées dont orderedAt < $before
 */
public function findStalledOrders(\DateTimeImmutable $before): array;
```

### Requêtes Repository détaillées

#### UserRepository::findInactiveSince

```php
public function findInactiveSince(\DateTimeImmutable $before): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.lastLoginAt IS NOT NULL')
        ->andWhere('u.lastLoginAt < :before')
        ->setParameter('before', $before)
        ->getQuery()
        ->getResult();
}
```

#### UserRepository::findUnverifiedSince

```php
public function findUnverifiedSince(\DateTimeImmutable $before): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.verifiedAt IS NULL')
        ->andWhere('u.createdAt IS NOT NULL')
        ->andWhere('u.createdAt < :before')
        ->setParameter('before', $before)
        ->getQuery()
        ->getResult();
}
```

### Détail de la requête OrderRepository::findStalledOrders

```php
public function findStalledOrders(\DateTimeImmutable $before): array
{
    return $this->createQueryBuilder('o')
        ->andWhere('o.status NOT IN (:excluded)')
        ->setParameter('excluded', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])
        ->andWhere('o.orderedAt < :before')
        ->setParameter('before', $before)
        ->orderBy('o.orderedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0 Console Component, Doctrine ORM 3
- **Pattern** : Étendre `Symfony\Component\Console\Command\Command`. Utiliser `#[AsCommand]` attribute de Symfony 8.0
- **Style** : Suivre les conventions Symfony Console — `configure()`, `execute()`. Messages avec `$io = new SymfonyStyle($input, $output)`
- **Repository** : Ajouter des méthodes typées sur `UserRepository` et `OrderRepository` avec `@return` PHPDoc précisant `User[]` / `Order[]`
- **createdAt** : Initialiser dans le constructeur de `User` : `$this->createdAt = new \DateTimeImmutable()`. Le `default: CURRENT_TIMESTAMP` en BDD sert de filet de sécurité
- **Migration de données** : Ajouter une ligne `UPDATE "user" SET created_at = NOW() WHERE created_at IS NULL` dans la migration générée pour les comptes existant avant l'ajout du champ
- **Transactions** : La suppression multiple dans les commandes doit être faite dans une transaction (`$this->entityManager->wrapInTransaction(...)`)
- **Dry-run** : L'option `--dry-run` doit exécuter la requête de sélection sans appel à `remove()` / `flush()`
- **Soft delete** : Aucun soft delete — suppression définitive (`$em->remove()`)
- **Pas de message** : Aucune notification email lors de la suppression
- **Autowiring** : Injecter `EntityManagerInterface` dans les commandes (les services sont auto-wired)

### Tests à implémenter

Voir Tâche #004 pour les tests.

#### Tests unitaires (prévus)
- **Fichier** : `tests/Unit/Command/PurgeInactiveUsersCommandTest.php`
- Scénario : Exécution de la commande avec dry-run, avec suppression réelle

- **Fichier** : `tests/Unit/Command/ListStalledOrdersCommandTest.php`
- Scénario : Exécution de la commande avec différentes données

#### Tests d'intégration
- Scénario : Les repositories retournent les bons résultats
- Données : Insérer des utilisateurs avec différentes dates de dernière connexion

### Documentation

#### Documentation à mettre à jour
- `README.md` : Ajouter une section "Commandes console" listant les 3 commandes avec leur description (voir Tâche #004)

### Exemples d'utilisation

```bash
# Purge des comptes inactifs (dry-run)
bin/console app:users:purge-inactive --dry-run

# Purge des comptes inactifs (exécution réelle)
bin/console app:users:purge-inactive

# Liste des commandes en attente
bin/console app:orders:list-stalled
```
