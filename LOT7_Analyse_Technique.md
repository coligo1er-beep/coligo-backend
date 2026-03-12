# Analyse Technique - LOT 7 : Suivi de Livraison

Ce document détaille l'architecture technique nécessaire pour le suivi en temps réel des colis, la gestion des preuves de livraison et la machine à états des envois.

## 1. Structure de la Base de Données

### Table `shipment_tracking` (Nouveauté)
Permet de conserver l'historique des positions GPS du transporteur.
- `id` (bigint, PK)
- `shipment_id` (foreignId) : Lien vers le colis.
- `latitude` (decimal, 10, 8)
- `longitude` (decimal, 11, 8)
- `speed` (float, nullable) : Vitesse actuelle (km/h).
- `created_at` (timestamp) : Date/heure du relevé.

### Table `delivery_proofs` (Nouveauté)
Stocke les preuves numériques de la transaction.
- `id` (bigint, PK)
- `shipment_id` (foreignId)
- `type` (enum) : `pickup_photo`, `delivery_photo`, `sender_signature`.
- `file_path` (string) : Chemin vers le fichier sur le disque.
- `metadata` (json) : Coordonnées GPS et horodatage au moment de la capture.
- `created_at`

### Table `shipment_status_history` (Audit)
- `id`
- `shipment_id`
- `status` (string)
- `notes` (text, nullable)
- `created_at`

---

## 2. API Endpoints

| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| **PATCH** | `/shipments/{id}/pickup` | Confirmer la récupération du colis (Transporteur). |
| **POST** | `/shipments/{id}/tracking` | Envoyer les coordonnées GPS actuelles (Transporteur). |
| **GET** | `/shipments/{id}/tracking` | Récupérer la position actuelle et l'historique (Expéditeur). |
| **POST** | `/shipments/{id}/proof` | Uploader une photo ou signature de preuve. |
| **PATCH** | `/shipments/{id}/deliver` | Marquer comme arrivé à destination (Transporteur). |
| **POST** | `/shipments/{id}/confirm-receipt` | Confirmer la réception finale et clore (Expéditeur). |
| **GET** | `/delivery-history` | Liste des livraisons terminées pour l'utilisateur. |

---

## 3. Architecture Temps Réel (WebSockets)

Le suivi GPS doit être fluide sans rafraîchir l'application.

### Channels
- `private-tracking.{shipment_id}` : Channel sécurisé accessible uniquement par l'expéditeur et le transporteur de ce colis.

### Événements
1.  **`LocationUpdated`** : Diffusé à chaque fois que le transporteur envoie sa position.
    - *Payload* : `{ latitude, longitude, speed, eta_minutes }`
2.  **`ShipmentStatusChanged`** : Diffusé lors des changements d'états (En transit, Livré).

---

## 4. Logique Métier & Sécurité

### Machine à États (State Machine)
Nous devons empêcher les sauts d'états illogiques :
- Un colis ne peut pas être "Livré" s'il n'est pas passé par "En transit".
- Le statut "Livré" est un état temporaire qui doit être validé par la "Confirmation de réception" de l'expéditeur.

### Sécurité du Suivi GPS
- **Validation temporelle** : L'accès aux coordonnées GPS dans le channel `private-tracking` n'est autorisé que si le statut est `in_transit`. 
- Dès que le colis est marqué comme `delivered` ou `cancelled`, le channel de tracking est fermé.

---

## 5. Gestion des Fichiers (Stockage)

### Signatures
Les signatures tactiles seront envoyées en format **Base64** ou **SVG/PNG** depuis Flutter, puis stockées sous forme de fichier image dans `storage/app/public/proofs/signatures/`.

### Photos de preuve
Stockées dans `storage/app/public/proofs/photos/` avec une nomenclature : `proof_{shipment_id}_{type}_{timestamp}.jpg`.
