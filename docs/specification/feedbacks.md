## 2025-05-24

* Créer un compte admin au démarrage de l'application s'il n'y en a pas déjà un qui a pour adresse email "admin@example.com" et qui a pour mot de passe "admin". [PROCESSED: story-010]
* Modifier les unités d'achat des produits : les avocats, les mangues, les ananas se vendent à la pièce, les herbes aromatiques se vendent au bouquet, les fraises en barquettes de 250g et tout le reste se vendent au kilogramme. Selon si les produits sont vendus à la pièce, au kg, au bouquet, en bouteille ou en barquette : le préciser dans la description du produit. [PROCESSED: story-011]
* Modifie les produits du top 3 des produits les plus vendus : fraises, fromage Saint-Marcellin et ananas. [PROCESSED: story-011]
* Enlève la catégorie 'Légumes bio' et remplace par 'Produits locaux d'exception'. Dans cette catégorie, rajoute les produits suivants : Noix de Grenoble AOC, huile de noix de Grenoble AOC bouteille 250ml, Fromage Bleu du Vercors-Sassenage, Fromage Saint-Marcellin, Chocolat Bonnat en tablettes de 100g. [PROCESSED: story-011]
* Rajouter un style CSS aéré avec la lib tailwindcss (header/navbar, footer, cartes produits, aperçu du contenu du panier au survol de la souris) [PROCESSED: story-012]
* Pour valider un panier, et donc valider une commande, simuler un formulaire de paiement, un écran de confirmation de commande et envoyez un email de confirmation. [PROCESSED: story-013]
* Dans la vue Accueil : Ajoute un petit de texte de bienvenue et de présentation de la boutique. Le top 3 des produits les plus vendus doit s'afficher juste en dessous. Puis affiche les catégories des produits. [PROCESSED: story-014]

* J'ai ajouté des images, dans le dossier public/assets/images, format .png pour les produits (dossier products) pour la page d'accueil (dossier home/images) et pour ajouter une petite icône utilisateur dans le header (dossier avatars, user pour le rôle user et admin pour le rôle admin). associe ces images aux cartes produits, pour l'image 'welcome' implémente là dans la vue home. [PROCESSED: story-015]
* Efface les placeholders en format jpg du dossier products. [PROCESSED: story-015]
* Quand je teste manuellement le paiement d'une commande, j'ai un message d'erreur : il faut absolument corriger ce bug majeur. [PROCESSED: story-016]
