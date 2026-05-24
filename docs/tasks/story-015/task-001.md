# Tâche #001 - Story #015 : Migration des chemins d'images produits

## Objectif
Créer une migration Doctrine qui met à jour le champ `image` des 20 produits pour pointer vers les fichiers PNG réels dans `public/assets/images/products/`, et supprimer les éventuels fichiers JPG résiduels.

## Contexte
- Story #015 : `docs/stories/story-015.md`
- Exécution : En premier (ordre séquentiel décidé)
- Dépend de : Story #004 (catalogue produits)
- Nécessaire pour : Tâche #004 (fallback image non disponible)
- Fichier de référence : `migrations/Version20260524210000.php`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Les produits en base de données référencent des images JPG qui n'existent pas (ex: `images/products/pomme-golden.jpg`). Les fichiers PNG réels sont dans `public/assets/images/products/` avec des noms différents (souvent au pluriel). Tu dois créer une migration qui corrige ces chemins.

**Important :** Les nouvelles routes d'image doivent utiliser le préfixe `assets/images/products/` (sans `public/`), car Symfony résout les assets relatifs au dossier `public/`.

**Mapping Produit → Fichier PNG :**

| ID | Nom produit | Fichier PNG |
|----|-------------|-------------|
| 1 | Pomme Golden | `assets/images/products/pommes.png` |
| 2 | Banane | `assets/images/products/bananes.png` |
| 3 | Orange | `assets/images/products/oranges.png` |
| 4 | Fraise | `assets/images/products/fraises.png` |
| 5 | Avocat | `assets/images/products/avocats.png` |
| 6 | Mangue | `assets/images/products/mangues.png` |
| 7 | **Carotte** | *Pas d'image → laisser NULL* |
| 8 | Salade | `assets/images/products/salades.png` |
| 9 | Tomate | `assets/images/products/tomates.png` |
| 10 | Concombre | `assets/images/products/concombres.png` |
| 11 | Courgette | `assets/images/products/courgettes.png` |
| 12 | Basilic | `assets/images/products/basilic.png` |
| 13 | Menthe | `assets/images/products/menthe.png` |
| 14 | **Persil** | *Pas d'image → laisser NULL* |
| 15 | Ananas | `assets/images/products/ananas.png` |
| 16 | Noix de Grenoble AOC | `assets/images/products/noix-grenoble.png` |
| 17 | Huile de noix de Grenoble AOC | `assets/images/products/huile-noix-grenoble.png` |
| 18 | Fromage Bleu du Vercors-Sassenage | `assets/images/products/bleu-vercors.png` |
| 19 | Fromage Saint-Marcellin | `assets/images/products/saint-marcellin.png` |
| 20 | Chocolat Bonnat | `assets/images/products/chocolat-bonnat.png` |

**Cas nominaux :**
- Les 18 produits avec PNG associé voient leur champ `image` mis à jour
- Les produits 7 (Carotte) et 14 (Persil) n'ont pas d'image → leur champ `image` est mis à NULL

**Cas limites :**
- Si un produit a déjà une image PNG correcte, ne pas modifier
- Si un produit référencé n'existe pas dans la table, ignorer silencieusement

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/Product.php` | Modifier | Changer `setImage(string $image)` en `setImage(?string $image)` |
| `migrations/Version20260524220000.php` | Créer | Nouvelle migration : ALTER TABLE + UPDATE des chemins |
| `public/assets/images/products/` | Modifier | Supprimer les fichiers `.jpg` s'il en existe |

### Signatures

```php
// Dans la classe de migration
public function getDescription(): string
public function up(Schema $schema): void
public function down(Schema $schema): void
```

### Contraintes techniques
- **Framework** : Symfony 8.0 / Doctrine Migrations
- **Convention** : Suivre le pattern des migrations existantes dans `migrations/Version*.php`
- **SQL** : Utiliser `UPDATE product SET image = ... WHERE id = ...` via `$this->addSql()`
- **Ne pas supprimer** les migrations existantes — créer une nouvelle migration par dessus
- **Ne pas** altérer les données de seed (`INSERT INTO`) dans la migration existante
- **Entité** : Modifier `Product::setImage(string $image): self` → `setImage(?string $image): self` pour accepter NULL (cohérent avec `private ?string $image = null`)
- **Schéma** : Ajouter `ALTER TABLE product ALTER COLUMN image DROP NOT NULL` dans la même migration
- **Pas de `down()`** : La méthode `down()` peut être vide ou lever une exception — pas de rollback prévu
- **JPG** : Supprimer les fichiers `.jpg` dans `public/assets/images/products/` s'ils existent

### Tests à implémenter

#### Test d'intégration
- **Fichier** : `tests/Integration/ProductImageMigrationTest.php`
- **Classe** : `App\Tests\Integration\ProductImageMigrationTest`
- Scénario : Vérifier que le dossier `public/assets/images/products/` ne contient plus de fichiers `.jpg`
  - Données : Lister les fichiers du dossier
  - Résultat attendu : Aucun fichier `.jpg` présent
- Scénario : Vérifier que les produits 1-6, 8-13, 15-20 ont une image PNG correcte
  - Résultat attendu : Le champ `image` se termine par `.png` et le fichier existe

### Exemples d'utilisation

```php
// Extrait de la méthode up()
$this->addSql("UPDATE product SET image = 'assets/images/products/pommes.png' WHERE id = 1 AND image IS DISTINCT FROM 'assets/images/products/pommes.png'");
// Pour les produits sans image :
$this->addSql("UPDATE product SET image = NULL WHERE id = 7");
```
