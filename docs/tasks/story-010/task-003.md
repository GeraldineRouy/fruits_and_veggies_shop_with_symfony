# Tâche #003 - Story #010 : Documentation du compte admin par défaut

## Objectif

Mettre à jour le README.md pour documenter l'existence du compte administrateur par défaut et recommander de changer le mot de passe après la première connexion.

## Contexte

- Story #010 : `docs/stories/story-010.md`
- Dépend de : Tâche #001 (migration du compte admin)
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Ajouter une section "Compte administrateur par défaut" dans le README.md, après la section "Installation" et avant "Tests".

**Cas nominaux :**
- La section est visible dans le README avec les identifiants du compte admin par défaut
- La section recommande explicitement de changer le mot de passe après la première connexion
- La section mentionne la commande pour exécuter les migrations (déjà documentée mais rappel utile)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `README.md` | Modifier | Ajouter la section "Compte administrateur par défaut" |

### Contenu à ajouter

Ajouter une section intitulée `## Compte administrateur par défaut` entre la section `## Démarrage rapide (après installation)` et la section `## Tests`.

La section doit contenir :

```markdown
## Compte administrateur par défaut

Après installation et exécution des migrations, un compte administrateur est automatiquement créé :

| Champ | Valeur |
|-------|--------|
| Email | `admin@example.com` |
| Mot de passe | `admin` |

Ce compte possède le rôle `ROLE_ADMIN` et permet d'accéder au dashboard d'administration sur `/admin`.

> ⚠️ **Recommandation de sécurité** : Après votre première connexion, changez le mot de passe de ce compte. Ne conservez pas le mot de passe par défaut en production.
```

### Contraintes techniques

- **Emplacement** : Insérer la nouvelle section entre `## Démarrage rapide (après installation)` et `## Tests`
- **Format** : Tableau markdown pour les identifiants, bloc de citation pour l'avertissement de sécurité
- **Style** : Respecter le format markdown existant du README (mêmes titres, même structure)
- **Langue** : Français (comme le reste du README)

### Tests à implémenter

Aucun test pour cette tâche purement documentaire.

### Documentation

La documentation modifiée est le README.md lui-même. Aucun autre fichier de documentation n'est concerné.

### Exemples d'utilisation

```bash
# Voir la documentation mise à jour
cat README.md
```
