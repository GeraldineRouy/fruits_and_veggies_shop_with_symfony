# Tâche #006 - Story #009 : Mise à jour du README

## Objectif
Documenter les fonctionnalités d'administration dans le README.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Tâche #002, Tâche #003, Tâche #004
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Mettre à jour le fichier `README.md` pour documenter :
1. Le dashboard administration
2. La gestion des catégories (CRUD)
3. La gestion des produits (CRUD)
4. La migration de données d'exemple

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `README.md` | Modifier | Ajouter section administration |

### Contenu à ajouter

Après la section "Administration des utilisateurs" existante, ajouter :

#### Dashboard administration

Le dashboard admin (`/admin`) est accessible aux utilisateurs avec le rôle `ROLE_ADMIN`. Il centralise l'accès à toutes les fonctionnalités d'administration.

Routes du dashboard :

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin` | Dashboard principal (liens vers toutes les sections) |

#### Gestion des catégories

L'admin peut gérer les catégories de produits via les routes suivantes, avec pagination (20 par page) :

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/admin/categories` | Liste paginée des catégories |
| GET | `/admin/categories/new` | Formulaire de création |
| POST | `/admin/categories/new` | Création |
| GET | `/admin/categories/{id}/edit` | Formulaire d'édition |
| POST | `/admin/categories/{id}/edit` | Modification |
| POST | `/admin/categories/{id}/delete` | Suppression |

#### Gestion des produits

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

#### Données d'exemple

Une migration Doctrine insère des données d'exemple (fruits, légumes, herbes aromatiques) :

```bash
# Exécuter les migrations (déjà fait à l'installation)
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

Données insérées :
- 5 catégories : Fruits, Légumes, Fruits exotiques, Légumes bio, Herbes aromatiques
- 15 produits avec associations aux catégories

### Contraintes techniques
- **Format** : Markdown, suivre le style existant du README (tableaux, titres, etc.)
- **Style** : Même ton et niveau de détail que les sections existantes
