# LOT 3 - Gestion des Routes de Transport
## Guide d'intégration API

### Vue d'ensemble

L'API LOT 3 implémente la gestion complète des routes de transport pour la plateforme Coligo. Elle permet aux transporteurs de créer, modifier, publier leurs itinéraires avec des points d'arrêt (waypoints) géolocalisés et de gérer leurs demandes de transport.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentification
Tous les endpoints du LOT 3 nécessitent une authentification Bearer token et un utilisateur de type "transporter" ou "both".

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Lister toutes les routes

**GET** `/routes`

Récupérer la liste paginée des routes publiées avec filtrage avancé.

```bash
# Toutes les routes publiées
curl -X GET http://127.0.0.1:8000/api/routes \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"

# Avec filtres
curl -X GET "http://127.0.0.1:8000/api/routes?status=published&from_city=Paris&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `per_page` (int, optionnel) - Éléments par page (défaut: 15, max: 100)
- `status` (enum, optionnel) - Filtrer par statut : "draft", "published", "in_progress", "completed", "cancelled"
- `from_city` (string, optionnel) - Filtrer par ville de départ
- `to_city` (string, optionnel) - Filtrer par ville d'arrivée
- `departure_date_from` (date, optionnel) - Date de départ minimum (YYYY-MM-DD)
- `departure_date_to` (date, optionnel) - Date de départ maximum (YYYY-MM-DD)
- `vehicle_type` (enum, optionnel) - Type de véhicule : "truck", "van", "car", "motorcycle"
- `min_capacity` (int, optionnel) - Capacité minimale en kg

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Routes retrieved successfully",
  "data": {
    "routes": [
      {
        "id": 1,
        "user_id": 1,
        "title": "Route Paris-Lyon Express",
        "description": "Transport quotidien Paris-Lyon avec arrêt Dijon",
        "from_city": "Paris",
        "from_address": "Gare de Lyon, Paris",
        "to_city": "Lyon",
        "to_address": "Gare Part-Dieu, Lyon",
        "departure_date": "2026-01-20",
        "arrival_date": "2026-01-20",
        "status": "published",
        "vehicle_type": "truck",
        "capacity_kg": 5000,
        "price_per_km": "2.50",
        "is_flexible": true,
        "created_at": "2026-01-17T11:30:00.000000Z",
        "updated_at": "2026-01-17T11:35:00.000000Z",
        "user": {
          "id": 1,
          "first_name": "Pierre",
          "last_name": "Transport",
          "email": "pierre@transport.com",
          "user_type": "transporter"
        },
        "waypoints": [
          {
            "id": 1,
            "address": "Gare de Dijon",
            "city": "Dijon",
            "stop_order": 1,
            "estimated_arrival": "2026-01-20T14:00:00.000000Z"
          }
        ]
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

### 2. Créer une nouvelle route

**POST** `/routes`

Créer une nouvelle route de transport (statut "draft" par défaut).

```bash
curl -X POST http://127.0.0.1:8000/api/routes \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Route Paris-Lyon Express",
    "description": "Transport quotidien avec arrêts multiples",
    "from_city": "Paris",
    "from_address": "Gare de Lyon, Paris",
    "to_city": "Lyon",
    "to_address": "Gare Part-Dieu, Lyon",
    "departure_date": "2026-01-20",
    "arrival_date": "2026-01-20",
    "vehicle_type": "truck",
    "capacity_kg": 5000,
    "price_per_km": 2.50,
    "is_flexible": true
  }'
```

**Champs requis :**
- `title` (string) - Titre de la route
- `from_city` (string) - Ville de départ
- `from_address` (string) - Adresse de départ
- `to_city` (string) - Ville d'arrivée
- `to_address` (string) - Adresse d'arrivée
- `departure_date` (date) - Date de départ (YYYY-MM-DD)
- `vehicle_type` (enum) - Type de véhicule : "truck", "van", "car", "motorcycle"
- `capacity_kg` (int) - Capacité en kg
- `price_per_km` (float) - Prix par kilomètre

**Champs optionnels :**
- `description` (string) - Description de la route
- `arrival_date` (date) - Date d'arrivée prévue
- `is_flexible` (boolean) - Route flexible (défaut: true)

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Route created successfully",
  "data": {
    "route": {
      "user_id": 1,
      "title": "Route Paris-Lyon Express",
      "description": "Transport quotidien avec arrêts multiples",
      "from_city": "Paris",
      "from_address": "Gare de Lyon, Paris",
      "to_city": "Lyon",
      "to_address": "Gare Part-Dieu, Lyon",
      "departure_date": "2026-01-20",
      "arrival_date": "2026-01-20",
      "vehicle_type": "truck",
      "capacity_kg": 5000,
      "price_per_km": "2.50",
      "is_flexible": true,
      "status": "draft",
      "id": 1,
      "created_at": "2026-01-17T11:30:00.000000Z",
      "updated_at": "2026-01-17T11:30:00.000000Z",
      "waypoints": []
    }
  }
}
```

**Erreurs possibles :**
- `403` - Utilisateur non autorisé (doit être transporter ou both)
- `422` - Erreur de validation (champs requis, formats invalides)

---

### 3. Consulter une route

**GET** `/routes/{id}`

Récupérer les détails complets d'une route spécifique avec ses waypoints.

```bash
curl -X GET http://127.0.0.1:8000/api/routes/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route retrieved successfully",
  "data": {
    "route": {
      "id": 1,
      "user_id": 1,
      "title": "Route Paris-Lyon Express",
      "description": "Transport quotidien Paris-Lyon avec arrêt Dijon",
      "from_city": "Paris",
      "from_address": "Gare de Lyon, Paris",
      "to_city": "Lyon",
      "to_address": "Gare Part-Dieu, Lyon",
      "departure_date": "2026-01-20",
      "arrival_date": "2026-01-20",
      "status": "published",
      "vehicle_type": "truck",
      "capacity_kg": 5000,
      "price_per_km": "2.50",
      "is_flexible": true,
      "created_at": "2026-01-17T11:30:00.000000Z",
      "updated_at": "2026-01-17T11:35:00.000000Z",
      "user": {
        "id": 1,
        "first_name": "Pierre",
        "last_name": "Transport",
        "email": "pierre@transport.com"
      },
      "waypoints": [
        {
          "id": 1,
          "route_id": 1,
          "address": "Gare de Dijon",
          "city": "Dijon",
          "country": "France",
          "latitude": "47.32200000",
          "longitude": "5.04150000",
          "stop_order": 1,
          "estimated_arrival": "2026-01-20T14:00:00.000000Z",
          "is_flexible": true,
          "created_at": "2026-01-17T11:32:00.000000Z"
        }
      ]
    }
  }
}
```

**Erreurs possibles :**
- `404` - Route non trouvée

---

### 4. Modifier une route

**PUT** `/routes/{id}`

Mettre à jour une route (uniquement si statut = "draft").

```bash
curl -X PUT http://127.0.0.1:8000/api/routes/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "title": "Route Paris-Lyon Express Modifiée",
    "price_per_km": 3.00,
    "capacity_kg": 6000
  }'
```

**Champs modifiables :**
- Tous les champs sauf `id`, `user_id`, `status`, `created_at`
- Les champs non fournis restent inchangés

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route updated successfully",
  "data": {
    "route": {
      "id": 1,
      "title": "Route Paris-Lyon Express Modifiée",
      "price_per_km": "3.00",
      "capacity_kg": 6000,
      "updated_at": "2026-01-17T12:00:00.000000Z"
    }
  }
}
```

**Erreurs possibles :**
- `403` - Route publiée (modification interdite) ou non propriétaire
- `404` - Route non trouvée
- `422` - Erreur de validation

---

### 5. Publier une route

**POST** `/routes/{id}/publish`

Publier une route pour la rendre visible aux expéditeurs.

```bash
curl -X POST http://127.0.0.1:8000/api/routes/1/publish \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route published successfully",
  "data": {
    "route": {
      "id": 1,
      "status": "published",
      "updated_at": "2026-01-17T11:35:00.000000Z"
    }
  }
}
```

**Erreurs possibles :**
- `400` - Route ne peut pas être publiée (statut invalide)
- `404` - Route non trouvée

---

### 6. Finaliser une route

**POST** `/routes/{id}/complete`

Marquer une route comme terminée.

```bash
curl -X POST http://127.0.0.1:8000/api/routes/1/complete \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route completed successfully",
  "data": {
    "route": {
      "id": 1,
      "status": "completed",
      "updated_at": "2026-01-17T18:00:00.000000Z"
    }
  }
}
```

---

### 7. Supprimer une route

**DELETE** `/routes/{id}`

Supprimer définitivement une route et tous ses waypoints.

```bash
curl -X DELETE http://127.0.0.1:8000/api/routes/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route deleted successfully"
}
```

**Erreurs possibles :**
- `403` - Non propriétaire ou route en cours
- `404` - Route non trouvée

---

### 8. Recherche avancée

**GET** `/routes/search`

Rechercher des routes par mots-clés et filtres.

```bash
# Recherche textuelle
curl -X GET "http://127.0.0.1:8000/api/routes/search?q=Paris&status=published" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"

# Recherche par itinéraire
curl -X GET "http://127.0.0.1:8000/api/routes/search?from_city=Paris&to_city=Lyon" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `q` (string, optionnel) - Recherche dans titre et description
- `from_city` (string, optionnel) - Ville de départ
- `to_city` (string, optionnel) - Ville d'arrivée
- `status` (string, optionnel) - Statut de la route
- `vehicle_type` (string, optionnel) - Type de véhicule
- `page` (int, optionnel) - Pagination

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": {
    "routes": [
      {
        "id": 1,
        "title": "Route Paris-Lyon Express",
        "from_city": "Paris",
        "to_city": "Lyon",
        "vehicle_type": "truck",
        "capacity_kg": 5000,
        "price_per_km": "2.50",
        "status": "published",
        "user": {
          "id": 1,
          "first_name": "Pierre",
          "last_name": "Transport"
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

### 9. Routes à proximité

**GET** `/routes/nearby`

Trouver des routes proches d'une position géographique.

```bash
curl -X GET "http://127.0.0.1:8000/api/routes/nearby?latitude=48.8566&longitude=2.3522&radius=50" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres requis :**
- `latitude` (float) - Latitude de recherche
- `longitude` (float) - Longitude de recherche

**Paramètres optionnels :**
- `radius` (int) - Rayon de recherche en km (défaut: 50, max: 500)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Nearby routes retrieved successfully",
  "data": {
    "routes": [
      {
        "id": 1,
        "title": "Route Paris-Lyon Express",
        "from_city": "Paris",
        "from_address": "Gare de Lyon, Paris",
        "distance_km": 1.2,
        "vehicle_type": "truck",
        "capacity_kg": 5000,
        "price_per_km": "2.50",
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

### 10. Mes routes

**GET** `/routes/my`

Récupérer toutes les routes de l'utilisateur connecté (tous statuts).

```bash
curl -X GET "http://127.0.0.1:8000/api/routes/my?status=draft&page=1" \
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
  "message": "User routes retrieved successfully",
  "data": {
    "routes": [
      {
        "id": 1,
        "title": "Route Paris-Lyon Express",
        "status": "published",
        "departure_date": "2026-01-20",
        "created_at": "2026-01-17T11:30:00.000000Z",
        "waypoints": [
          {
            "id": 1,
            "city": "Dijon",
            "stop_order": 1
          }
        ]
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

## Gestion des Waypoints

### 11. Lister les waypoints d'une route

**GET** `/routes/{id}/waypoints`

Récupérer tous les waypoints d'une route triés par ordre d'arrêt.

```bash
curl -X GET http://127.0.0.1:8000/api/routes/1/waypoints \
  -H "Accept: application/json"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Waypoints retrieved successfully",
  "data": {
    "waypoints": [
      {
        "id": 1,
        "route_id": 1,
        "address": "Gare de Dijon",
        "city": "Dijon",
        "country": "France",
        "latitude": "47.32200000",
        "longitude": "5.04150000",
        "stop_order": 1,
        "estimated_arrival": "2026-01-20T14:00:00.000000Z",
        "is_flexible": true,
        "created_at": "2026-01-17T11:32:00.000000Z",
        "updated_at": "2026-01-17T11:32:00.000000Z"
      }
    ]
  }
}
```

---

### 12. Ajouter un waypoint

**POST** `/routes/{id}/waypoints`

Ajouter un point d'arrêt à une route (uniquement si statut = "draft").

```bash
curl -X POST http://127.0.0.1:8000/api/routes/1/waypoints \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "address": "Gare de Dijon",
    "city": "Dijon",
    "country": "France",
    "latitude": 47.3220,
    "longitude": 5.0415,
    "stop_order": 1,
    "estimated_arrival": "2026-01-20T14:00:00",
    "is_flexible": true
  }'
```

**Champs requis :**
- `address` (string) - Adresse du point d'arrêt
- `city` (string) - Ville du point d'arrêt
- `country` (string) - Pays du point d'arrêt
- `stop_order` (int) - Ordre d'arrêt (1, 2, 3...)

**Champs optionnels :**
- `latitude` (float) - Latitude GPS
- `longitude` (float) - Longitude GPS
- `estimated_arrival` (datetime) - Heure d'arrivée estimée
- `is_flexible` (boolean) - Arrêt flexible (défaut: true)

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Waypoint created successfully",
  "data": {
    "waypoint": {
      "route_id": 1,
      "address": "Gare de Dijon",
      "city": "Dijon",
      "country": "France",
      "latitude": "47.32200000",
      "longitude": "5.04150000",
      "stop_order": 1,
      "estimated_arrival": "2026-01-20T14:00:00.000000Z",
      "is_flexible": true,
      "id": 1,
      "created_at": "2026-01-17T11:32:00.000000Z",
      "updated_at": "2026-01-17T11:32:00.000000Z"
    }
  }
}
```

**Règles automatiques :**
- Si le `stop_order` existe déjà, les waypoints suivants sont décalés automatiquement
- Insertion intelligente dans la séquence d'arrêts

**Erreurs possibles :**
- `403` - Route publiée (modification interdite) ou non propriétaire
- `404` - Route non trouvée
- `422` - Erreur de validation

---

### 13. Modifier un waypoint

**PUT** `/routes/{id}/waypoints/{waypointId}`

Mettre à jour un waypoint existant.

```bash
curl -X PUT http://127.0.0.1:8000/api/routes/1/waypoints/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "address": "Gare de Dijon Centre",
    "estimated_arrival": "2026-01-20T15:00:00",
    "stop_order": 2
  }'
```

**Champs modifiables :**
- Tous les champs sauf `id`, `route_id`, `created_at`
- Les champs non fournis restent inchangés

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Waypoint updated successfully",
  "data": {
    "waypoint": {
      "id": 1,
      "address": "Gare de Dijon Centre",
      "estimated_arrival": "2026-01-20T15:00:00.000000Z",
      "stop_order": 2,
      "updated_at": "2026-01-17T12:00:00.000000Z"
    }
  }
}
```

**Règles automatiques :**
- Changement de `stop_order` réorganise automatiquement les autres waypoints
- Gestion intelligente des conflits d'ordre

---

### 14. Supprimer un waypoint

**DELETE** `/routes/{id}/waypoints/{waypointId}`

Supprimer un waypoint d'une route.

```bash
curl -X DELETE http://127.0.0.1:8000/api/routes/1/waypoints/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Waypoint deleted successfully"
}
```

**Règles automatiques :**
- Réorganisation automatique du `stop_order` des waypoints restants
- Décrémentation des ordres supérieurs

**Erreurs possibles :**
- `403` - Route publiée (modification interdite) ou non propriétaire
- `404` - Waypoint ou route non trouvé

---

### 15. Demandes de transport pour une route

**GET** `/routes/{id}/requests`

Récupérer les demandes de transport compatibles avec une route (fonctionnalité future).

```bash
curl -X GET http://127.0.0.1:8000/api/routes/1/requests \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Route requests retrieved successfully",
  "data": {
    "requests": [],
    "route": {
      "id": 1,
      "title": "Route Paris-Lyon Express",
      "waypoints": [
        {
          "id": 1,
          "city": "Dijon",
          "stop_order": 1
        }
      ]
    }
  }
}
```

---

## Règles métier importantes

### Permissions utilisateur
- **Création** : Seuls les utilisateurs "transporter" ou "both" peuvent créer des routes
- **Modification** : Seul le propriétaire peut modifier ses routes
- **Consultation** : Toutes les routes publiées sont visibles par tous
- **Waypoints** : Seul le propriétaire peut gérer les waypoints

### Statuts des routes
- **draft** - Brouillon (modifiable, invisible aux autres)
- **published** - Publiée (visible, non modifiable)
- **in_progress** - En cours de trajet
- **completed** - Terminée
- **cancelled** - Annulée

### Gestion des waypoints
- **Ordre intelligent** : Insertion et modification automatique des `stop_order`
- **Flexibilité** : Chaque waypoint peut être flexible ou fixe
- **Géolocalisation** : Coordonnées GPS optionnelles pour chaque arrêt
- **Limite** : Aucune limite sur le nombre de waypoints

### Recherche géolocalisée
- **Algorithme** : Utilise la formule de Haversine pour calculer les distances
- **Rayon par défaut** : 50 km
- **Rayon maximum** : 500 km
- **Précision** : Calcul en mètres, retour en kilomètres

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `400` - Requête invalide (statut incorrect, paramètres manquants)
- `401` - Non authentifié
- `403` - Accès interdit (permissions utilisateur, route publiée)
- `404` - Ressource non trouvée
- `422` - Erreur de validation
- `500` - Erreur serveur

---

## Exemples d'erreurs de validation

### Permissions insuffisantes :
```json
{
  "success": false,
  "message": "Only transporters and both type users can create routes"
}
```

### Champs requis manquants :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "from_city": ["The from city field is required."],
    "to_city": ["The to city field is required."],
    "departure_date": ["The departure date field is required."],
    "vehicle_type": ["The vehicle type field is required."]
  }
}
```

### Modification interdite :
```json
{
  "success": false,
  "message": "Cannot modify waypoints of published route"
}
```

### Route non trouvée :
```json
{
  "success": false,
  "message": "Route not found"
}
```

### Paramètres de géolocalisation invalides :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "latitude": ["The latitude must be between -90 and 90."],
    "radius": ["The radius may not be greater than 500."]
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
- Tester les filtres et la géolocalisation
- Visualiser les réponses d'exemple

---

**Version API** : 1.0.0
**Framework** : Laravel 8.x
**Authentification** : Laravel Sanctum
**Documentation** : OpenAPI 3.0