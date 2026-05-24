# Tâche #004 - Story #009 : Migration de données d'exemple

## Objectif
Créer une migration Doctrine qui insère des produits et catégories d'exemple (fruits, légumes) dans la base de données.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Rien (peut être fait en parallèle)
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer une migration Doctrine versionnée qui insère des données d'exemple. La migration doit être réversible (methode `down` qui supprime les données insérées).

**Catégories à créer :**
1. "Fruits" — "Fruits frais de saison"
2. "Légumes" — "Légumes frais de saison"
3. "Fruits exotiques" — "Fruits tropicaux et exotiques"
4. "Légumes bio" — "Légumes issus de l'agriculture biologique"
5. "Herbes aromatiques" — "Herbes et plantes aromatiques"

**Produits à créer (avec associations ManyToMany) :**

| Produit | Description | Prix | Image | Catégories |
|---------|-------------|------|-------|------------|
| Pomme Golden | Pomme Golden sucrée et juteuse | 2.50 | images/products/pomme-golden.jpg | Fruits |
| Banane | Banane mûre à point | 1.80 | images/products/banane.jpg | Fruits, Fruits exotiques |
| Orange | Orange sanguine de Sicile | 3.00 | images/products/orange.jpg | Fruits |
| Fraise | Fraise gariguette parfumée | 4.50 | images/products/fraise.jpg | Fruits |
| Avocat | Avocat prêt à déguster | 2.20 | images/products/avocat.jpg | Fruits exotiques, Légumes |
| Mangue | Mangue bio du Pérou | 3.50 | images/products/mangue.jpg | Fruits exotiques |
| Carotte | Carotte bio, lot de 500g | 1.50 | images/products/carotte.jpg | Légumes, Légumes bio |
| Salade | Salade verte croquante | 1.20 | images/products/salade.jpg | Légumes |
| Tomate | Tomate cœur de bœuf bio | 3.80 | images/products/tomate.jpg | Légumes, Légumes bio |
| Concombre | Concombre long | 1.60 | images/products/concombre.jpg | Légumes |
| Courgette | Courgette verte bio | 2.10 | images/products/courgette.jpg | Légumes, Légumes bio |
| Basilic | Basilic frais en pot | 2.00 | images/products/basilic.jpg | Herbes aromatiques |
| Menthe | Menthe fraîche en pot | 2.00 | images/products/menthe.jpg | Herbes aromatiques |
| Persil | Persil plat en botte | 1.00 | images/products/persil.jpg | Herbes aromatiques |
| Ananas | Ananas Victoria | 3.90 | images/products/ananas.jpg | Fruits exotiques |

**Cas nominaux :**
- `bin/console doctrine:migrations:migrate` insère les 5 catégories et 15 produits
- `bin/console doctrine:migrations:migrate <previous>` supprime les données insérées

**Gestion d'erreurs :**
- Utiliser `INSERT IGNORE` ou vérifier l'existence avant d'insérer pour éviter les doublons si la migration est rejouée
- Utiliser des IDs explicites (fixes) pour les catégories afin de garantir la réversibilité

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `migrations/VersionXXXXX.php` | Créer | Migration avec données d'exemple |
| `public/assets/images/products/` | Créer (dossier) | Dossier vide pour les images des produits d'exemple |

### Signatures

```php
// Migration générée avec `bin/console make:migration`
// Puis modifiée pour ajouter les INSERT dans up() et DELETE dans down()
```

### Contraintes techniques
- **Framework** : Doctrine Migrations
- **Génération** : Utiliser `bin/console make:migration` pour créer le fichier squelette, puis éditer
- **Ordre d'insertion** : Catégories d'abord, produits ensuite, puis associations product_category
- **IDs fixes** : Utiliser des IDs numériques explicites (1-5 pour catégories, 1-15 pour produits)
- **SQL pur** : Utiliser du SQL natif (`$this->addSql()`) pour les INSERT, pas DQL/ORM
- **Images** : Créer le dossier `public/assets/images/products/` (vide) — les images seront ajoutées manuellement plus tard. Ne PAS référencer d'images placeholder. Les produits d'exemple pointent vers des images qui n'existent pas encore ; le template admin gère l'affichage avec `asset()` qui sera silencieux si le fichier est absent.
- **Réversibilité** : La méthode `down()` doit supprimer tous les produits et catégories insérés

### Tests à implémenter
- Aucun test automatisé pour la migration (testée manuellement en exécutant `migrate` puis `migrate <previous>`)

### Documentation
- Documentée via la Tâche #006 (README)
