# Processus de commande

## Déroulement

1. Le client connecté ajoute des produits à son panier (`/panier`)
2. Depuis la page panier, il clique sur **"Valider la commande"**
3. La page de paiement simulé s'affiche (`/commande/paiement`) avec les champs pré-remplis
4. Le client clique sur **"Payer"** (pas de saisie nécessaire)
5. La commande est créée avec le statut `confirmed`
6. Un email de confirmation est envoyé automatiquement
7. Le client est redirigé vers la page de confirmation (`/commande/confirmation/{id}`)
8. Depuis la confirmation, le client peut retourner à l'accueil ou voir ses commandes

## Statuts d'une commande

| Statut | Signification |
|--------|---------------|
| `confirmed` | Commande confirmée, en attente de traitement |
| `preparing` | En cours de préparation |
| `shipped` | Expédiée |
| `delivered` | Livrée |
| `cancelled` | Annulée |

### Transitions autorisées

```
confirmed → preparing → shipped → delivered
```

Une commande peut être annulée depuis n'importe quel statut (par un admin) ou uniquement depuis `confirmed` (par le client).

## Règles d'annulation

- **Client** : peut annuler uniquement si le statut est `confirmed`
- **Admin** : peut annuler une commande quel que soit son statut, sauf si déjà `cancelled`

## Emails envoyés

- **Confirmation** : envoyé après la création de la commande
- **Changement de statut** : envoyé à chaque transition de statut (préparation, expédition, livraison, annulation)

## Administration

Les admins peuvent gérer les commandes via `/admin/commandes` :
- Lister toutes les commandes
- Voir le détail d'une commande
- Changer le statut d'une commande
- Annuler une commande
