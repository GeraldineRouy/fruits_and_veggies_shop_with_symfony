# Story #015 : Intégration des images produits et assets visuels

## Description
En tant que **visiteur**, je veux voir les images des produits associées à leurs fiches, une image de bienvenue sur la page d'accueil, et des icônes avatars dans le header selon mon rôle, afin d'avoir une expérience visuelle complète et soignée.

## Origine
Feedbacks du 2025-05-24 :
- "J'ai ajouté des images, dans le dossier public/assets/images, format .png pour les produits (dossier products) pour la page d'accueil (dossier home/images) et pour ajouter une petite icône utilisateur dans le header (dossier avatars, user pour le rôle user et admin pour le rôle admin). associe ces images aux cartes produits, pour l'image 'welcome' implémente là dans la vue home."
- "Efface les placeholders en format jpg du dossier products."

## Implémentation
- Association des images aux produits : remplir le champ `image` de l'entité Product avec le nom du fichier PNG correspondant
- Image welcome placée entre le texte de bienvenue et le top 3 des produits
- Avatar affiché à côté du nom/email dans le header
- Texte "Image non disponible" pour les produits sans image

## Critères d'acceptation
- [ ] Les images PNG dans `public/assets/images/products/` sont associées aux cartes produits via le champ `image` de l'entité Product
- [ ] L'image `welcome.png` dans `public/assets/images/home/` est affichée sur la page d'accueil entre le texte de bienvenue et le top 3
- [ ] L'icône avatar `user.png` s'affiche à côté du nom/email dans le header pour les utilisateurs connectés avec le rôle ROLE_USER
- [ ] L'icône avatar `admin.png` s'affiche à côté du nom/email dans le header pour les utilisateurs connectés avec le rôle ROLE_ADMIN
- [ ] Un texte "Image non disponible" est affiché pour les produits sans image associée
- [ ] Les placeholders JPG dans `public/assets/images/products/` sont supprimés
- [ ] Le design reste cohérent avec le style Tailwind (Story #012)

## Tests automatisés
- Test d'intégration : vérifier que les images produits sont bien rendues dans les cartes produits
- Test d'intégration : vérifier que l'image welcome est présente sur la page d'accueil
- Test d'intégration : vérifier que le dossier products ne contient plus de fichiers .jpg

## Documentation
- Documenter la structure des assets images dans le README

## Valeur utilisateur
Offre une expérience visuelle immersive avec des images réelles des produits, un accueil chaleureux avec une image de bienvenue, et une personnalisation du header selon le rôle de l'utilisateur.

## Dépendances
- Story #004 (catalogue produits)
- Story #012 (style Tailwind)
- Story #014 (page d'accueil)
