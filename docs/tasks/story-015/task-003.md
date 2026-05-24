# Tâche #003 - Story #015 : Avatars utilisateur dans le header

## Objectif
Ajouter une icône avatar dans le header à côté du prénom de l'utilisateur connecté, variant selon le rôle : `avatar_user.png` pour ROLE_USER, `avatar_admin.png` pour ROLE_ADMIN.

## Contexte
- Story #015 : `docs/stories/story-015.md`
- Exécution : Après Tâche #002 (ordre séquentiel décidé)
- Dépend de : Story #003 (authentification)
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Le header (`templates/base.html.twig`) affiche actuellement le prénom de l'utilisateur connecté (`{{ app.user.firstName }}`). Tu dois ajouter une image avatar circulaire à côté de ce prénom.

Les fichiers avatars sont dans `public/assets/images/avatars/` :
- `avatar_user.png` — pour les utilisateurs avec le rôle `ROLE_USER` (mais pas `ROLE_ADMIN`)
- `avatar_admin.png` — pour les utilisateurs avec le rôle `ROLE_ADMIN`

**Règle d'affichage :**
- Si l'utilisateur a `ROLE_ADMIN` → afficher `avatar_admin.png`
- Sinon (utilisateur connecté sans admin) → afficher `avatar_user.png`
- Ne rien afficher si l'utilisateur n'est pas connecté

**Cas nominaux :**
- L'utilisateur connecté avec ROLE_USER voit `avatar_user.png` à côté de son prénom dans le header desktop
- L'utilisateur connecté avec ROLE_ADMIN voit `avatar_admin.png` à côté de son prénom dans le header desktop
- L'avatar est une image circulaire de 32x32 pixels

**Cas limites :**
- Si l'utilisateur connecté n'a pas encore de prénom, afficher quand même l'avatar

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/base.html.twig` | Modifier | Ajouter l'avatar à côté du prénom dans le header (desktop) et dans le menu mobile |

### Contraintes techniques
- **Framework** : Twig + Tailwind CSS (CDN)
- **Style CSS** :
  - Image circulaire : `w-8 h-8 rounded-full object-cover`
  - Marges : `mr-2` (margin-right par rapport au prénom)
- **Asset** : Utiliser `{{ asset('assets/images/avatars/avatar_admin.png') }}`
- **Détermination du rôle** : Utiliser `is_granted('ROLE_ADMIN')` dans Twig
  ```twig
  {% if is_granted('ROLE_ADMIN') %}
      <img src="{{ asset('assets/images/avatars/avatar_admin.png') }}" alt="Admin" class="w-8 h-8 rounded-full object-cover mr-2">
  {% else %}
      <img src="{{ asset('assets/images/avatars/avatar_user.png') }}" alt="Utilisateur" class="w-8 h-8 rounded-full object-cover mr-2">
  {% endif %}
  ```
- **Modifications dans le template** :
  1. Section desktop (ligne ~84-86) : Ajouter l'avatar AVANT `<span class="text-sm">{{ app.user.firstName }}</span>`
  2. Section mobile (ligne ~107) : Ajouter l'avatar AVANT `<span class="block py-2 text-sm">{{ app.user.firstName }}</span>`

### Tests à implémenter

#### Test d'intégration
- **Fichier** : `tests/Integration/HeaderAvatarTest.php`
- **Classe** : `App\Tests\Integration\HeaderAvatarTest`
- Scénario 1 : Un utilisateur ROLE_USER voit `avatar_user.png` dans le header
  - Créer un utilisateur avec `ROLE_USER` uniquement
  - Se connecter
  - Requête sur `/`
  - Vérifier que la réponse contient `assets/images/avatars/avatar_user.png`
- Scénario 2 : Un utilisateur ROLE_ADMIN voit `avatar_admin.png` dans le header
  - Créer un utilisateur avec `ROLE_ADMIN`
  - Se connecter
  - Requête sur `/`
  - Vérifier que la réponse contient `assets/images/avatars/avatar_admin.png`
- Scénario 3 : Un visiteur non connecté ne voit pas d'avatar
  - Requête `GET /` sans connexion
  - Vérifier que la réponse NE contient PAS `avatars/`

### Résultat attendu

```twig
{# Dans la section desktop (ligne ~84) #}
<div class="hidden md:flex items-center space-x-2">
    {% if is_granted('ROLE_ADMIN') %}
        <img src="{{ asset('assets/images/avatars/avatar_admin.png') }}" alt="Admin" class="w-8 h-8 rounded-full object-cover mr-2">
    {% else %}
        <img src="{{ asset('assets/images/avatars/avatar_user.png') }}" alt="Utilisateur" class="w-8 h-8 rounded-full object-cover mr-2">
    {% endif %}
    <span class="text-sm">{{ app.user.firstName }}</span>
    <a href="{{ path('app_logout') }}" class="text-sm hover:text-brand-200 transition-colors">Déconnexion</a>
</div>

{# Dans la section mobile (ligne ~107) #}
<div class="flex items-center space-x-2 py-2">
    {% if is_granted('ROLE_ADMIN') %}
        <img src="{{ asset('assets/images/avatars/avatar_admin.png') }}" alt="Admin" class="w-8 h-8 rounded-full object-cover">
    {% else %}
        <img src="{{ asset('assets/images/avatars/avatar_user.png') }}" alt="Utilisateur" class="w-8 h-8 rounded-full object-cover">
    {% endif %}
    <span class="text-sm">{{ app.user.firstName }}</span>
</div>
```
