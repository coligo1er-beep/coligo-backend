# Analyse Technique - LOT 8 : Système d'Évaluation & Notation

Ce document détaille l'implémentation technique du système de réputation, incluant le stockage des avis, le calcul des moyennes et la gestion des badges.

## 1. Structure de la Base de Données

### Table `reviews`
Stocke les évaluations entre utilisateurs.
- `id` (bigint, PK)
- `match_id` (foreignId) : Lien vers la transaction terminée.
- `reviewer_id` (foreignId) : Celui qui laisse l'avis.
- `reviewed_id` (foreignId) : Celui qui reçoit l'avis.
- `rating` (tinyint) : Note de 1 à 5.
- `comment` (text, nullable)
- `criteria` (json) : Ponctualité, communication, soin (notes individuelles).
- `response` (text, nullable) : Réponse de la personne évaluée.
- `is_published` (boolean) : Pour la modération.
- `created_at` / `updated_at`

### Table `badges` (Référentiel)
- `id`
- `name` (string) : `identity_verified`, `expert`, `super_transporter`.
- `icon_path` (string)
- `description` (text)

### Table `user_badges` (Pivot)
- `user_id`
- `badge_id`
- `achieved_at` (timestamp)

---

## 2. API Endpoints

| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| **POST** | `/matches/{id}/reviews` | Soumettre un avis pour une livraison terminée. |
| **GET** | `/users/{id}/reviews` | Liste publique des avis d'un utilisateur. |
| **POST** | `/reviews/{id}/respond` | Répondre à un avis reçu. |
| **PUT** | `/reviews/{id}` | Modifier son avis (limité à 30 jours). |
| **GET** | `/users/{id}/badges` | Liste des badges obtenus par un utilisateur. |
| **POST** | `/reviews/{id}/report` | Signaler un avis inapproprié. |

---

## 3. Logique de Calcul (Moteurs de Réputation)

### Mise à jour de la note moyenne
Un **Observer** sur le modèle `Review` déclenchera une mise à jour de la colonne `rating_average` dans la table `users` à chaque création/modification.
```php
// Formule simple
$average = Review::where('reviewed_id', $userId)->avg('rating');
$user->update(['rating_average' => $average]);
```

### Attribution Automatique des Badges
Un service dédié `BadgeService` vérifiera les conditions après chaque livraison :
1.  **Expert** : `IF (completed_transports >= 20) -> Give Badge`.
2.  **Super Transporteur** : `IF (rating_average > 4.8 AND completed_transports >= 50) -> Give Badge`.

---

## 4. Sécurité & Anti-Fraude

1.  **Validation de la transaction :** Un utilisateur ne peut laisser un avis QUE si le statut du match est `completed`.
2.  **Unicité :** Un seul avis possible par match et par sens (Expéditeur ➔ Transporteur).
3.  **Délai de modification :** Verrouillage de l'édition après 30 jours.
4.  **Droit de réponse :** Une seule réponse autorisée par la personne évaluée, sans possibilité de noter en retour dans la réponse.

---

## 5. Intégration Front-End (Flutter)

### Composant de notation
- Utilisation d'un `RatingBar` pour la saisie simplifiée.
- Validation de la longueur du commentaire côté client avant l'envoi.

### Affichage du Profil
- Calcul et affichage des "Étoiles" (0.5 precision).
- Liste des avis avec pagination infinie pour éviter les lenteurs.
