# LOT 8 - Système d'Évaluation & Badges
## Guide d'intégration API

### Vue d'ensemble

L'API LOT 8 gère la réputation des utilisateurs à travers un système de notation par étoiles, des avis détaillés et des badges de fiabilité. Les évaluations sont liées à des livraisons terminées (`completed`), garantissant l'authenticité des avis.

## Base URL
```
http://31.97.68.223/api
```

---

## 1. Gestion des Avis (Reviews)

### Soumettre un avis
**POST** `/matches/{match_id}/reviews`

Permet de noter l'autre partie après une livraison réussie.

**Paramètres :**
- `rating` (int, 1-5, requis)
- `comment` (string, 10-500 car., optionnel)
- `criteria` (json, optionnel) : Détails comme `{"ponctualite": 5, "soin": 4}`

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Review submitted successfully.",
    "data": {
        "id": 1,
        "rating": 5,
        "comment": "Excellent transporteur, très rapide !"
    }
}
```

### Consulter les avis d'un utilisateur
**GET** `/users/{user_id}/reviews`

Récupère la liste publique des avis reçus par un utilisateur (pagination par 15).

### Répondre à un avis
**POST** `/reviews/{id}/respond`
- `response` (string, max 500 car.) : Droit de réponse unique.

---

## 2. Système de Badges

### Lister les badges d'un utilisateur
**GET** `/users/{id}/badges`

Retourne la liste des badges obtenus.

**Exemple de badges :**
- `identity_verified` : Identité confirmée.
- `expert` : Plus de 20 livraisons réussies.
- `super_transporter` : Note > 4.8 et haut volume.

---

## 3. Score de Réputation (Profile)

Chaque utilisateur possède maintenant deux nouveaux champs dans son profil (`GET /profile` ou `GET /users/{id}`) :
- `rating_average` (decimal) : Note moyenne sur 5.0.
- `total_reviews` (int) : Nombre total d'avis reçus.

---

## 🎨 Conseils d'Intégration Mobile

### Dashboard & Profil
- Affichez la note moyenne sous forme d'étoiles (ex: 4.5/5) à côté de la photo de profil.
- Affichez les badges sous forme de badges visuels (icônes) avec une interaction au clic pour afficher la description du badge.

### Formulaire de notation
- Proposez le formulaire dès que l'expéditeur clique sur "Confirmer la réception".
- Utilisez un slider ou des icônes d'étoiles interactives pour une meilleure UX.

### Modération
- Ajoutez un bouton "Signaler" sur chaque avis pour permettre aux utilisateurs de notifier les commentaires inappropriés à l'administration.
