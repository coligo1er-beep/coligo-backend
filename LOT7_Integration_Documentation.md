# LOT 7 - Système de Suivi de Livraison
## Guide d'intégration API & Tracking Temps Réel

### Vue d'ensemble

L'API LOT 7 permet de gérer tout le cycle de vie d'une livraison, de la récupération du colis par le transporteur jusqu'à la confirmation finale par l'expéditeur. Elle inclut l'historique GPS, les preuves par photo/signature et les notifications de changement de statut en temps réel via WebSockets.

## Base URL
```
http://31.97.68.223/api
```

## Authentification
Tous les endpoints nécessitent une authentification Bearer token.

---

## 1. Cycle de Vie de la Livraison

### Confirmer la prise en charge (Pickup)
**PATCH** `/shipments/{id}/pickup`
- **Utilisateur :** Transporteur assigné uniquement.
- **Action :** Passe le statut du colis de `matched` à `in_transit`.

### Marquer comme arrivé (Deliver)
**PATCH** `/shipments/{id}/deliver`
- **Utilisateur :** Transporteur assigné uniquement.
- **Action :** Passe le statut à `delivered`. Le colis attend maintenant la confirmation de l'expéditeur.

### Confirmer la réception finale
**POST** `/shipments/{id}/confirm-receipt`
- **Utilisateur :** Expéditeur uniquement.
- **Action :** Passe le statut à `completed`. Clôture officiellement la transaction et libère les fonds.

---

## 2. Suivi GPS en Temps Réel

### Envoyer la position actuelle
**POST** `/shipments/{id}/tracking`
- **Utilisateur :** Transporteur.
- **Paramètres :**
    - `latitude` (decimal, requis)
    - `longitude` (decimal, requis)
    - `speed` (float, optionnel)

### Récupérer l'historique de tracking
**GET** `/shipments/{id}/tracking`
- **Utilisateur :** Expéditeur ou Transporteur.
- **Réponse :** Liste paginée des dernières positions GPS.

### ⚡ WebSockets (Événements)
- **Channel :** `private-tracking.{shipment_id}`
- **Événement :** `location.updated`
- **Payload :** L'objet `tracking` avec latitude, longitude et vitesse.

---

## 3. Preuves de Livraison

### Uploader une preuve
**POST** `/shipments/{id}/proof`
- **Champs :**
    - `type` (enum) : `pickup_photo`, `delivery_photo`, `sender_signature`.
    - `file` (file) : Image JPG/PNG (max 5MB).
    - `metadata` (json, optionnel) : Infos GPS au moment de la prise.

---

## 🎨 Conseils d'Intégration Mobile

### Pour le Transporteur (Tracking)
- Implémentez un service de localisation en arrière-plan (Background Location) qui appelle le endpoint `POST /tracking` toutes les 5 à 10 minutes lorsque le statut est `in_transit`.

### Pour l'Expéditeur (Carte)
- Abonnez-vous au channel WebSocket dès que l'utilisateur ouvre la page de suivi.
- Utilisez l'événement `location.updated` pour déplacer le marqueur du transporteur sur la carte sans recharger la page.

### Preuves
- Pour la signature, utilisez un widget de dessin et envoyez le résultat en format Image (PNG) via le endpoint `/proof`.
