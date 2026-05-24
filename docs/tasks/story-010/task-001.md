# Tâche #001 - Story #010 : Migration de données Doctrine pour le compte admin

## Objectif

Créer une migration de données Doctrine qui insère un compte administrateur par défaut (`admin@example.com` / `admin`) si aucun utilisateur avec cet email n'existe déjà en base.

## Contexte

- Story #010 : `docs/stories/story-010.md`
- Dépend de : Story #003 (entité User, authentification), Story #002 (schéma de base)
- Nécessaire pour : Tâche #002 (tests), Tâche #003 (documentation)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer une migration de données Doctrine (classe `Version20260524130000`) qui insère un utilisateur admin avec les caractéristiques suivantes :

| Champ | Valeur |
|-------|--------|
| `email` | `admin@example.com` |
| `firstName` | `Admin` |
| `lastName` | `Admin` |
| `roles` | `["ROLE_ADMIN"]` (JSON) |
| `password` | Hash bcrypt de la chaîne `admin` (via `password_hash('admin', PASSWORD_BCRYPT)`) |
| `verifiedAt` | `CURRENT_TIMESTAMP` |
| `isActive` | `true` |
| `createdAt` | DEFAULT de la colonne (`CURRENT_TIMESTAMP`) |

**Cas nominaux :**
- Aucun admin n'existe → La migration crée le compte admin avec tous les champs requis
- Le compte admin existe déjà (même email) → La migration ne fait rien (idempotent)

**Cas limites :**
- Un utilisateur avec l'email `admin@example.com` existe déjà mais n'a pas le rôle `ROLE_ADMIN` → Conformément à la story, ne pas le modifier ni le recréer
- La table `user` est vide → La migration crée l'admin sans conflit

**Gestion d'erreurs :**
- Contrainte d'unicité sur `email` → Utiliser `ON CONFLICT (email) DO NOTHING` pour PostgreSQL
- La migration `down()` doit supprimer l'admin : `DELETE FROM "user" WHERE email = 'admin@example.com'`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `migrations/Version20260524130000.php` | Créer | Nouvelle migration de données pour le compte admin |

### Contraintes techniques

- **Framework** : Symfony 8.0 / Doctrine Migrations
- **Base de données** : PostgreSQL 16 — utiliser la syntaxe PostgreSQL (`ON CONFLICT`, `CURRENT_TIMESTAMP`)
- **Convention** : Suivre exactement le pattern de la migration existante `migrations/Version20260524120000.php` (même namespace `DoctrineMigrations`, extends `AbstractMigration`, méthodes `getDescription()`, `up()`, `down()`)
- **Nom de classe** : `Version20260524130000` (timestamp après la dernière migration existante)
- **Description** : `'Crée le compte administrateur par défaut (admin@example.com)'`
- **Hash du mot de passe** : Utiliser `password_hash('admin', PASSWORD_BCRYPT)` dans la méthode `up()` pour générer le hash au moment de l'exécution — le coût bcrypt par défaut est suffisant (le `auto` de Symfony utilise bcrypt)
- **Idempotence** : La migration doit pouvoir être exécutée plusieurs fois sans erreur ni duplication
- **Pas de service container** : Les migrations Doctrine n'ont pas accès au container Symfony — tout doit être fait en SQL brut avec `$this->addSql()`
- **Table `user`** : Le nom de la table est `"user"` (avec guillemets car c'est un mot réservé SQL)

### Structure de la migration

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le compte administrateur par défaut (admin@example.com)';
    }

    public function up(Schema $schema): void
    {
        // INSÉRER LE CODE ICI
    }

    public function down(Schema $schema): void
    {
        // INSÉRER LE CODE ICI
    }
}
```

Détail du SQL pour `up()` :

```sql
INSERT INTO "user" (email, first_name, last_name, roles, password, verified_at, is_active)
VALUES (
    'admin@example.com',
    'Admin',
    'Admin',
    '["ROLE_ADMIN"]',
    '<hash_php_genere>',
    CURRENT_TIMESTAMP,
    true
)
ON CONFLICT (email) DO NOTHING
```

Note : le hash bcrypt est généré en PHP via `password_hash()`, pas codé en dur dans le SQL. Tu dois interpoler la variable PHP contenant le hash dans la chaîne SQL.

### Tests à implémenter

Les tests sont détaillés dans la Tâche #002. Cette tâche se concentre uniquement sur la migration.

### Documentation

Aucune documentation spécifique pour cette tâche — la mise à jour du README est dans la Tâche #003.

### Exemples d'utilisation

Une fois la migration exécutée :

```bash
# Exécuter la migration
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Vérifier que l'admin existe
docker compose exec app php bin/console dbal:run-sql "SELECT email, roles, is_active FROM \"user\" WHERE email = 'admin@example.com'"
```

Résultat attendu :
```
 email              | roles                 | is_active
--------------------+-----------------------+----------
 admin@example.com  | ["ROLE_ADMIN"]        | t
```
