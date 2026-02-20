# LOT 2 - Gestion des Annonces de Colis
## Guide d'intégration API

### Vue d'ensemble

L'API LOT 2 implémente la gestion complète des annonces de colis pour la plateforme Coligo. Elle inclut la création, modification, publication, recherche des shipments ainsi que la gestion complète des photos associées (upload, réorganisation, suppression).

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentification
Tous les endpoints du LOT 2 nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Lister tous les shipments

**GET** `/shipments`

Récupérer la liste paginée des shipments publiés avec filtrage avancé.

```bash
# Tous les shipments publiés
curl -X GET http://127.0.0.1:8000/api/shipments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"

# Avec filtres
curl -X GET "http://127.0.0.1:8000/api/shipments?status=published&city=Paris&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `status` (enum, optionnel) - Filtrer par statut : "draft", "published", "matched", "in_transit", "delivered", "cancelled"
- `city` (string, optionnel) - Filtrer par ville de départ ou d'arrivée
- `date_from` (date, optionnel) - Filtrer par date de collecte minimale
- `max_weight` (float, optionnel) - Filtrer par poids maximum

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipments retrieved successfully",
  "data": {
    "shipments": [
      {
        "id": 1,
        "user_id": 1,
        "title": "Livraison Paris-Lyon",
        "description": "Transport de documents importants de Paris à Lyon",
        "weight": "2.50",
        "length": null,
        "width": null,
        "height": null,
        "fragile": false,
        "dangerous_goods": false,
        "pickup_address": "Place de la République, Paris",
        "pickup_city": "Paris",
        "pickup_postal_code": null,
        "pickup_country": "France",
        "pickup_latitude": "48.86710000",
        "pickup_longitude": "2.36410000",
        "pickup_date_from": "2026-01-20T09:00:00.000000Z",
        "pickup_date_to": "2026-01-20T18:00:00.000000Z",
        "delivery_address": "Gare de Lyon Part-Dieu, Lyon",
        "delivery_city": "Lyon",
        "delivery_postal_code": null,
        "delivery_country": "France",
        "delivery_latitude": "45.75970000",
        "delivery_longitude": "4.84220000",
        "delivery_date_limit": "2026-01-21T18:00:00.000000Z",
        "budget_min": "50.00",
        "budget_max": "80.00",
        "currency": "EUR",
        "status": "published",
        "priority": "normal",
        "special_instructions": null,
        "published_at": "2026-01-17T11:25:24.000000Z",
        "created_at": "2026-01-17T11:24:58.000000Z",
        "updated_at": "2026-01-17T11:25:24.000000Z",
        "user": {
          "id": 1,
          "first_name": "Jean",
          "last_name": "Dupont",
          "email": "jean.dupont@example.com",
          "user_type": "sender",
          "is_verified": false
        },
        "photos": [],
        "primary_photo": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1,
      "per_page": 15
    }
  }
}
```

---

### 2. Créer un nouveau shipment

**POST** `/shipments`

Créer une nouvelle annonce de colis (statut "draft" par défaut).

```bash
curl -X POST http://127.0.0.1:8000/api/shipments \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Livraison Paris-Lyon",
    "description": "Transport de documents importants",
    "weight": 2.5,
    "fragile": true,
    "pickup_address": "Place de la République, Paris",
    "pickup_city": "Paris",
    "pickup_country": "France",
    "pickup_latitude": 48.8671,
    "pickup_longitude": 2.3641,
    "pickup_date_from": "2026-01-20T09:00:00",
    "pickup_date_to": "2026-01-20T18:00:00",
    "delivery_address": "Gare de Lyon Part-Dieu, Lyon",
    "delivery_city": "Lyon",
    "delivery_country": "France",
    "delivery_latitude": 45.7597,
    "delivery_longitude": 4.8422,
    "delivery_date_limit": "2026-01-21T18:00:00",
    "budget_min": 50.00,
    "budget_max": 80.00,
    "priority": "urgent"
  }'
```

**Champs requis :**
- `title` (string) - Titre de l'annonce
- `description` (string) - Description du colis
- `weight` (float) - Poids en kg
- `pickup_address` (string) - Adresse de collecte
- `pickup_city` (string) - Ville de collecte
- `pickup_country` (string) - Pays de collecte
- `pickup_date_from` (datetime) - Début fenêtre de collecte
- `pickup_date_to` (datetime) - Fin fenêtre de collecte
- `delivery_address` (string) - Adresse de livraison
- `delivery_city` (string) - Ville de livraison
- `delivery_country` (string) - Pays de livraison
- `delivery_date_limit` (datetime) - Date limite de livraison
- `budget_min` (float) - Budget minimum
- `budget_max` (float) - Budget maximum

**Champs optionnels :**
- `length`, `width`, `height` (float) - Dimensions en cm
- `fragile` (boolean) - Colis fragile (défaut: false)
- `dangerous_goods` (boolean) - Marchandises dangereuses (défaut: false)
- `pickup_latitude`, `pickup_longitude` (float) - Coordonnées GPS collecte
- `delivery_latitude`, `delivery_longitude` (float) - Coordonnées GPS livraison
- `currency` (string) - Devise (défaut: EUR)
- `priority` (enum) - Priorité : "low", "normal", "high", "urgent" (défaut: normal)
- `special_instructions` (string) - Instructions spéciales

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Shipment created successfully",
  "data": {
    "shipment": {
      "user_id": 1,
      "title": "Livraison Paris-Lyon",
      "description": "Transport de documents importants",
      "weight": "2.50",
      "fragile": true,
      "pickup_address": "Place de la République, Paris",
      "pickup_city": "Paris",
      "pickup_country": "France",
      "pickup_latitude": "48.86710000",
      "pickup_longitude": "2.36410000",
      "pickup_date_from": "2026-01-20T09:00:00.000000Z",
      "pickup_date_to": "2026-01-20T18:00:00.000000Z",
      "delivery_address": "Gare de Lyon Part-Dieu, Lyon",
      "delivery_city": "Lyon",
      "delivery_country": "France",
      "delivery_latitude": "45.75970000",
      "delivery_longitude": "4.84220000",
      "delivery_date_limit": "2026-01-21T18:00:00.000000Z",
      "budget_min": "50.00",
      "budget_max": "80.00",
      "currency": "EUR",
      "priority": "urgent",
      "status": "draft",
      "id": 1,
      "created_at": "2026-01-17T11:24:58.000000Z",
      "updated_at": "2026-01-17T11:24:58.000000Z",
      "photos": [],
      "primary_photo": null
    }
  }
}
```

**Erreurs possibles :**
- `422` - Erreur de validation (champs requis, formats invalides)

---

### 3. Consulter un shipment

**GET** `/shipments/{id}`

Récupérer les détails complets d'un shipment spécifique.

```bash
curl -X GET http://127.0.0.1:8000/api/shipments/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipment retrieved successfully",
  "data": {
    "shipment": {
      "id": 1,
      "user_id": 1,
      "title": "Livraison Paris-Lyon",
      "description": "Transport de documents importants de Paris à Lyon",
      "weight": "2.50",
      "pickup_address": "Place de la République, Paris",
      "pickup_city": "Paris",
      "delivery_address": "Gare de Lyon Part-Dieu, Lyon",
      "delivery_city": "Lyon",
      "budget_min": "50.00",
      "budget_max": "80.00",
      "status": "published",
      "created_at": "2026-01-17T11:24:58.000000Z",
      "updated_at": "2026-01-17T11:25:24.000000Z",
      "user": {
        "id": 1,
        "first_name": "Jean",
        "last_name": "Dupont",
        "email": "jean.dupont@example.com"
      },
      "photos": [
        {
          "id": 1,
          "file_name": "shipment_1_photo_1.jpg",
          "url": "/storage/shipments/shipment_1_photo_1.jpg",
          "is_primary": true,
          "sort_order": 0
        }
      ],
      "primary_photo": {
        "id": 1,
        "url": "/storage/shipments/shipment_1_photo_1.jpg"
      },
      "matches": []
    }
  }
}
```

**Erreurs possibles :**
- `404` - Shipment non trouvé

---

### 4. Modifier un shipment

**PUT** `/shipments/{id}`

Mettre à jour un shipment (uniquement si statut = "draft").

```bash
curl -X PUT http://127.0.0.1:8000/api/shipments/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Livraison Urgente Paris-Lyon",
    "priority": "urgent",
    "budget_max": 100.00
  }'
```

**Champs modifiables :**
- Tous les champs sauf `id`, `user_id`, `status`, `published_at`
- Les champs non fournis restent inchangés

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipment updated successfully",
  "data": {
    "shipment": {
      "id": 1,
      "title": "Livraison Urgente Paris-Lyon",
      "priority": "urgent",
      "budget_max": "100.00",
      "updated_at": "2026-01-17T12:00:00.000000Z"
    }
  }
}
```

**Erreurs possibles :**
- `403` - Shipment publié (modification interdite)
- `404` - Shipment non trouvé
- `422` - Erreur de validation

---

### 5. Publier un shipment

**PATCH** `/shipments/{id}/publish`

Publier un shipment pour le rendre visible aux transporteurs.

```bash
curl -X PATCH http://127.0.0.1:8000/api/shipments/1/publish \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipment published successfully",
  "data": {
    "shipment": {
      "id": 1,
      "status": "published",
      "published_at": "2026-01-17T11:25:24.000000Z",
      "updated_at": "2026-01-17T11:25:24.000000Z"
    }
  }
}
```

**Erreurs possibles :**
- `400` - Shipment ne peut pas être publié (statut invalide)
- `404` - Shipment non trouvé

---

### 6. Annuler un shipment

**PATCH** `/shipments/{id}/cancel`

Annuler un shipment publié.

```bash
curl -X PATCH http://127.0.0.1:8000/api/shipments/1/cancel \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipment cancelled successfully",
  "data": {
    "shipment": {
      "id": 1,
      "status": "cancelled",
      "updated_at": "2026-01-17T11:55:00.000000Z"
    }
  }
}
```

---

### 7. Supprimer un shipment

**DELETE** `/shipments/{id}`

Supprimer définitivement un shipment et toutes ses photos.

```bash
curl -X DELETE http://127.0.0.1:8000/api/shipments/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Shipment deleted successfully"
}
```

**Erreurs possibles :**
- `404` - Shipment non trouvé

---

### 8. Recherche avancée

**GET** `/shipments/search`

Rechercher des shipments par mots-clés et filtres.

```bash
# Recherche textuelle
curl -X GET "http://127.0.0.1:8000/api/shipments/search?q=documents&pickup_city=Paris" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"

# Recherche par itinéraire
curl -X GET "http://127.0.0.1:8000/api/shipments/search?from_city=Paris&to_city=Lyon" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `q` (string, optionnel) - Recherche dans titre et description
- `pickup_city` (string, optionnel) - Ville de départ
- `delivery_city` (string, optionnel) - Ville d'arrivée
- `from_city` (string, optionnel) - Alias pour pickup_city
- `to_city` (string, optionnel) - Alias pour delivery_city
- `page` (int, optionnel) - Pagination

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": {
    "shipments": [
      {
        "id": 1,
        "title": "Transport de documents urgents",
        "pickup_city": "Paris",
        "delivery_city": "Lyon",
        "weight": "2.50",
        "budget_min": "50.00",
        "budget_max": "80.00",
        "status": "published",
        "user": {
          "id": 1,
          "first_name": "Jean",
          "last_name": "Dupont"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  }
}
```

---

### 9. Shipments à proximité

**GET** `/shipments/nearby`

Trouver des shipments proches d'une position géographique.

```bash
curl -X GET "http://127.0.0.1:8000/api/shipments/nearby?latitude=48.8566&longitude=2.3522&radius=50" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres requis :**
- `latitude` (float) - Latitude de recherche
- `longitude` (float) - Longitude de recherche

**Paramètres optionnels :**
- `radius` (int) - Rayon de recherche en km (défaut: 50)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Nearby shipments retrieved successfully",
  "data": {
    "shipments": [
      {
        "id": 1,
        "title": "Livraison Paris-Lyon",
        "pickup_city": "Paris",
        "pickup_latitude": "48.86710000",
        "pickup_longitude": "2.36410000",
        "distance_km": 1.2,
        "budget_max": "80.00",
        "status": "published"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  }
}
```

---

### 10. Mes shipments

**GET** `/shipments/my-shipments`

Récupérer tous les shipments de l'utilisateur connecté (tous statuts).

```bash
curl -X GET "http://127.0.0.1:8000/api/shipments/my-shipments?status=draft&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres optionnels :**
- `status` (enum, optionnel) - Filtrer par statut
- `page` (int, optionnel) - Pagination

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "User shipments retrieved successfully",
  "data": {
    "shipments": [
      {
        "id": 1,
        "title": "Livraison Paris-Lyon",
        "status": "published",
        "created_at": "2026-01-17T11:24:58.000000Z",
        "photos": [],
        "primary_photo": null,
        "matches": []
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  }
}
```

---

## Gestion des Photos

### 11. Lister les photos d'un shipment

**GET** `/shipments/{id}/photos`

Récupérer toutes les photos d'un shipment triées par ordre.

```bash
curl -X GET http://127.0.0.1:8000/api/shipments/1/photos \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Photos retrieved successfully",
  "data": {
    "photos": [
      {
        "id": 1,
        "file_name": "1_1768649236_0.png",
        "file_size": 70,
        "mime_type": "image/png",
        "is_primary": true,
        "sort_order": 0,
        "url": "/storage/shipments/1_1768649236_0.png",
        "created_at": "2026-01-17T11:27:16.000000Z"
      }
    ]
  }
}
```

---

### 12. Upload de photos

**POST** `/shipments/{id}/photos`

Télécharger des photos pour un shipment (maximum 5 photos par shipment).

```bash
curl -X POST http://127.0.0.1:8000/api/shipments/1/photos \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -F "photos[]=@/path/to/photo1.jpg" \
  -F "photos[]=@/path/to/photo2.png" \
  -F "primary_photo_index=0"
```

**Champs requis :**
- `photos[]` (array of files) - Images (JPG, PNG, max 5MB chacune)

**Champs optionnels :**
- `primary_photo_index` (int) - Index de la photo principale (0-based)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Photos uploaded successfully",
  "data": {
    "photos": [
      {
        "id": 1,
        "file_name": "1_1768649236_0.jpg",
        "file_size": 2048,
        "mime_type": "image/jpeg",
        "is_primary": true,
        "sort_order": 0,
        "url": "/storage/shipments/1_1768649236_0.jpg",
        "created_at": "2026-01-17T11:27:16.000000Z"
      },
      {
        "id": 2,
        "file_name": "1_1768649236_1.png",
        "file_size": 1500,
        "mime_type": "image/png",
        "is_primary": false,
        "sort_order": 1,
        "url": "/storage/shipments/1_1768649236_1.png",
        "created_at": "2026-01-17T11:27:16.000000Z"
      }
    ],
    "uploaded_count": 2,
    "total_photos": 2
  }
}
```

**Erreurs possibles :**
- `400` - Trop de photos (limite de 5 dépassée)
- `404` - Shipment non trouvé
- `422` - Format ou taille de fichier invalide

---

### 13. Supprimer une photo

**DELETE** `/shipments/{id}/photos/{photoId}`

Supprimer une photo spécifique d'un shipment.

```bash
curl -X DELETE http://127.0.0.1:8000/api/shipments/1/photos/2 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Photo deleted successfully"
}
```

**Règles automatiques :**
- Si la photo supprimée était la photo principale, la première photo restante devient principale
- Le fichier est supprimé du stockage

**Erreurs possibles :**
- `404` - Photo ou shipment non trouvé

---

### 14. Définir photo principale

**PUT** `/shipments/{id}/photos/{photoId}/primary`

Définir une photo comme photo principale du shipment.

```bash
curl -X PUT http://127.0.0.1:8000/api/shipments/1/photos/3/primary \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Primary photo set successfully",
  "data": {
    "photo": {
      "id": 3,
      "file_name": "1_1768649500_2.jpg",
      "is_primary": true,
      "url": "/storage/shipments/1_1768649500_2.jpg"
    }
  }
}
```

**Règles automatiques :**
- L'ancienne photo principale perd automatiquement ce statut
- Une seule photo principale par shipment

---

### 15. Réorganiser les photos

**PUT** `/shipments/{id}/photos/reorder`

Modifier l'ordre d'affichage des photos d'un shipment.

```bash
curl -X PUT http://127.0.0.1:8000/api/shipments/1/photos/reorder \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "photo_orders": [
      {"photo_id": 2, "sort_order": 0},
      {"photo_id": 1, "sort_order": 1},
      {"photo_id": 3, "sort_order": 2}
    ]
  }'
```

**Champs requis :**
- `photo_orders` (array) - Tableau des nouveaux ordres
  - `photo_id` (int) - ID de la photo
  - `sort_order` (int) - Nouvelle position (0-based)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Photos reordered successfully"
}
```

**Erreurs possibles :**
- `422` - Photo non trouvée ou appartient à un autre shipment

---

## Règles métier importantes

### Permissions utilisateur
- **Création** : Seuls les utilisateurs "sender" ou "both" peuvent créer des shipments
- **Modification** : Seul le propriétaire peut modifier ses shipments
- **Consultation** : Tous les shipments publiés sont visibles par tous
- **Photos** : Seul le propriétaire peut gérer les photos

### Statuts des shipments
- **draft** - Brouillon (modifiable, invisible aux autres)
- **published** - Publié (visible, non modifiable)
- **matched** - Appairé avec un transporteur
- **in_transit** - En cours de transport
- **delivered** - Livré
- **cancelled** - Annulé

### Gestion des photos
- **Limite** : Maximum 5 photos par shipment
- **Formats** : JPG, JPEG, PNG uniquement
- **Taille** : Maximum 5MB par photo
- **Photo principale** : Une seule par shipment, automatiquement définie
- **Nommage** : Format `{shipment_id}_{timestamp}_{index}.{extension}`

### Géolocalisation
- **Coordonnées optionnelles** : Latitude/longitude pour pickup et delivery
- **Recherche proximité** : Calcul de distance avec rayon configurable
- **Unité** : Distances en kilomètres

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `400` - Requête invalide (trop de photos, statut incorrect)
- `401` - Non authentifié
- `403` - Accès interdit (modification shipment publié, permissions utilisateur)
- `404` - Ressource non trouvée
- `422` - Erreur de validation
- `500` - Erreur serveur

---

## Exemples d'erreurs de validation

### Champs requis manquants :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "pickup_city": ["The pickup city field is required."],
    "pickup_country": ["The pickup country field is required."],
    "pickup_date_from": ["The pickup date from field is required."],
    "delivery_date_limit": ["The delivery date limit field is required."]
  }
}
```

### Trop de photos :
```json
{
  "success": false,
  "message": "Maximum 5 photos allowed. Current: 3, Trying to add: 3"
}
```

### Modification interdite :
```json
{
  "success": false,
  "message": "Cannot update shipment in current status"
}
```

### Format de fichier invalide :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "photos.0": ["The photos.0 must be an image.", "The photos.0 may not be greater than 5120 kilobytes."]
  }
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://127.0.0.1:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints directement
- Voir les schémas de données détaillés
- Gérer l'authentification Bearer
- Tester l'upload de photos
- Visualiser les réponses d'exemple

---

**Version API** : 1.0.0
**Framework** : Laravel 8.x
**Authentification** : Laravel Sanctum
**Documentation** : OpenAPI 3.0