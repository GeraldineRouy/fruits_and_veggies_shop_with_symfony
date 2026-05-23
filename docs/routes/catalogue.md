# Routes du catalogue

## Page d'accueil
- **Route** : `app_home`
- **URL** : `GET /`
- **Controller** : `HomeController::index`
- **Description** : Affiche la page d'accueil avec la liste des catégories

## Liste des produits par catégorie
- **Route** : `app_shop_category`
- **URL** : `GET /boutique/{id}`
- **Controller** : `ShopController::category`
- **Paramètres** :
  - `id` (int) : ID de la catégorie
  - `page` (int, optionnel, défaut: 1) : Numéro de page dans la query string
- **Description** : Affiche les produits d'une catégorie avec pagination (12 par page)

## Fiche détaillée d'un produit
- **Route** : `app_shop_product`
- **URL** : `GET /boutique/produit/{id}`
- **Controller** : `ShopController::product`
- **Paramètres** :
  - `id` (int) : ID du produit
- **Description** : Affiche la fiche détaillée d'un produit (nom, description, image, prix, catégories)
