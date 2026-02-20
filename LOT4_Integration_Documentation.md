# LOT 4 - Système de Matching & Propositions
## Guide d'intégration API

### Vue d'ensemble

L'API LOT 4 implémente le système complet de matching et de propositions pour la plateforme Coligo. Elle inclut les suggestions automatiques de correspondances, la gestion des propositions entre transporteurs et expéditeurs, un système de communication intégré, et des analyses détaillées des performances du matching.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentification
Tous les endpoints du LOT 4 nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Obtenir des suggestions de matching

**GET** `/matches/suggestions`

Obtenir des suggestions automatiques de correspondances pour un shipment ou une route.

```bash
# Suggestions pour un shipment
curl -X GET "http://127.0.0.1:8000/api/matches/suggestions?type=shipment&id=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"

# Suggestions pour une route
curl -X GET "http://127.0.0.1:8000/api/matches/suggestions?type=route&id=5&limit=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `type` (string, requis) - Type : "shipment" ou "route"
- `id` (int, requis) - ID du shipment ou de la route
- `limit` (int, optionnel) - Nombre de suggestions (défaut: 5, max: 20)

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Suggestions retrieved successfully",
    "data": {
        "suggestions": [
            {
                "id": 1,
                "route_id": 5,
                "matching_score": 85.50,
                "distance_km": 25.30,
                "estimated_duration_hours": 2.5,
                "proposed_price": 150.00,
                "route": {
                    "id": 5,
                    "title": "Paris - Lyon Express",
                    "departure_city": "Paris",
                    "arrival_city": "Lyon",
                    "user": {
                        "first_name": "Marie",
                        "last_name": "Martin"
                    }
                }
            }
        ],
        "algorithm_info": {
            "distance_weight": 0.4,
            "date_weight": 0.3,
            "capacity_weight": 0.2,
            "price_weight": 0.1
        }
    }
}
```

### 2. Créer une proposition de matching

**POST** `/matches`

Créer une nouvelle proposition de correspondance entre un shipment et une route.

```bash
curl -X POST http://127.0.0.1:8000/api/matches \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "shipment_id": 1,
    "route_id": 5,
    "proposed_price": 150.00,
    "pickup_datetime": "2026-01-20T10:00:00Z",
    "delivery_datetime": "2026-01-20T18:00:00Z",
    "transporter_message": "Je peux prendre en charge cette livraison rapidement"
  }'
```

**Champs requis :**
- `shipment_id` (int) - ID du shipment
- `route_id` (int) - ID de la route
- `proposed_price` (decimal) - Prix proposé
- `pickup_datetime` (datetime) - Date/heure de collecte
- `delivery_datetime` (datetime) - Date/heure de livraison

**Champs optionnels :**
- `transporter_message` (string) - Message du transporteur

**Réponse (201 Created) :**
```json
{
    "success": true,
    "message": "Match created successfully",
    "data": {
        "match": {
            "id": 15,
            "status": "pending",
            "proposed_price": 150.00,
            "matching_score": 85.50,
            "pickup_datetime": "2026-01-20T10:00:00Z",
            "delivery_datetime": "2026-01-20T18:00:00Z"
        }
    }
}
```

### 3. Voir les détails d'une correspondance

**GET** `/matches/{id}`

Récupérer les détails complets d'une correspondance.

```bash
curl -X GET http://127.0.0.1:8000/api/matches/15 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "match": {
            "id": 15,
            "status": "pending",
            "proposed_price": 150.00,
            "final_price": null,
            "matching_score": 85.50,
            "distance_km": 25.30,
            "shipment": {
                "title": "Livraison urgente Paris",
                "weight_kg": 15.5,
                "pickup_city": "Paris"
            },
            "route": {
                "title": "Paris - Lyon Express",
                "departure_city": "Paris",
                "arrival_city": "Lyon"
            },
            "transporter": {
                "first_name": "Marie",
                "last_name": "Martin"
            },
            "sender": {
                "first_name": "Jean",
                "last_name": "Dupont"
            }
        }
    }
}
```

### 4. Accepter une correspondance

**PUT** `/matches/{id}/accept`

Accepter une proposition de correspondance (action expéditeur).

```bash
curl -X PUT http://127.0.0.1:8000/api/matches/15/accept \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "final_price": 140.00,
    "sender_response": "Prix négocié accepté, merci!"
  }'
```

**Champs optionnels :**
- `final_price` (decimal) - Prix final négocié
- `sender_response` (string) - Réponse de l'expéditeur

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Match accepted successfully",
    "data": {
        "match": {
            "id": 15,
            "status": "accepted",
            "final_price": 140.00,
            "accepted_at": "2026-01-17T14:30:00Z"
        }
    }
}
```

### 5. Rejeter une correspondance

**PUT** `/matches/{id}/reject`

Rejeter une proposition de correspondance.

```bash
curl -X PUT http://127.0.0.1:8000/api/matches/15/reject \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "sender_response": "Prix trop élevé, désolé"
  }'
```

**Champs optionnels :**
- `sender_response` (string) - Raison du refus

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Match rejected successfully",
    "data": {
        "match": {
            "id": 15,
            "status": "rejected",
            "rejected_at": "2026-01-17T14:30:00Z"
        }
    }
}
```

### 6. Finaliser une correspondance

**PUT** `/matches/{id}/complete`

Marquer une correspondance comme terminée.

```bash
curl -X PUT http://127.0.0.1:8000/api/matches/15/complete \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "completion_notes": "Livraison effectuée avec succès"
  }'
```

**Champs optionnels :**
- `completion_notes` (string) - Notes de finalisation

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Match completed successfully",
    "data": {
        "match": {
            "id": 15,
            "status": "completed",
            "completed_at": "2026-01-17T18:00:00Z"
        }
    }
}
```

### 7. Mes correspondances

**GET** `/matches/my`

Récupérer les correspondances de l'utilisateur connecté.

```bash
curl -X GET "http://127.0.0.1:8000/api/matches/my?status=pending&role=sender" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page
- `status` (string, optionnel) - Filtrer par statut
- `role` (string, optionnel) - "sender" ou "transporter"

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "matches": [
            {
                "id": 15,
                "status": "pending",
                "role": "sender",
                "proposed_price": 150.00,
                "matching_score": 85.50,
                "created_at": "2026-01-17T10:00:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 25
        }
    }
}
```

---

## Système de Communication

### 8. Lister les messages d'une correspondance

**GET** `/matches/{id}/messages`

Récupérer l'historique des messages d'une correspondance.

```bash
curl -X GET "http://127.0.0.1:8000/api/matches/15/messages?page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `per_page` (int, optionnel) - Messages par page (défaut: 20, max: 100)

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "messages": [
            {
                "id": 45,
                "message": "Bonjour, je peux livrer demain",
                "message_type": "text",
                "created_at": "2026-01-17T10:30:00Z",
                "read_at": null,
                "sender": {
                    "first_name": "Marie",
                    "last_name": "Martin"
                }
            }
        ],
        "match": {
            "id": 15,
            "status": "pending"
        }
    }
}
```

### 9. Envoyer un message

**POST** `/matches/{id}/messages`

Envoyer un nouveau message dans une correspondance.

```bash
curl -X POST http://127.0.0.1:8000/api/matches/15/messages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "message": "Parfait, à quelle heure puis-je passer récupérer le colis?",
    "message_type": "text"
  }'
```

**Champs requis :**
- `message` (string, max: 2000) - Contenu du message

**Champs optionnels :**
- `message_type` (enum) - "text" ou "system" (défaut: "text")

**Réponse (201 Created) :**
```json
{
    "success": true,
    "message": "Message sent successfully",
    "data": {
        "message": {
            "id": 46,
            "message": "Parfait, à quelle heure puis-je passer récupérer le colis?",
            "message_type": "text",
            "created_at": "2026-01-17T10:35:00Z"
        }
    }
}
```

### 10. Marquer un message comme lu

**PUT** `/matches/{id}/messages/{messageId}/read`

Marquer un message spécifique comme lu.

```bash
curl -X PUT http://127.0.0.1:8000/api/matches/15/messages/45/read \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "Message marked as read"
}
```

### 11. Marquer tous les messages comme lus

**PUT** `/matches/{id}/messages/mark-all-read`

Marquer tous les messages d'une correspondance comme lus.

```bash
curl -X PUT http://127.0.0.1:8000/api/matches/15/messages/mark-all-read \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
    "success": true,
    "message": "All messages marked as read",
    "data": {
        "marked_count": 3
    }
}
```

### 12. Nombre de messages non lus

**GET** `/matches/{id}/messages/unread-count`

Obtenir le nombre de messages non lus dans une correspondance.

```bash
curl -X GET http://127.0.0.1:8000/api/matches/15/messages/unread-count \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "unread_count": 2
    }
}
```

---

## Analytics et Métriques

### 13. Configuration de l'algorithme

**GET** `/matching/algorithm/config`

Obtenir la configuration actuelle de l'algorithme de matching.

```bash
curl -X GET http://127.0.0.1:8000/api/matching/algorithm/config \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "algorithm_config": {
            "distance_weight": 0.4,
            "date_weight": 0.3,
            "capacity_weight": 0.2,
            "price_weight": 0.1,
            "max_distance_km": 50,
            "max_date_diff_hours": 24,
            "min_matching_score": 30,
            "auto_suggest_enabled": true
        },
        "last_updated": "2026-01-17T10:00:00Z"
    }
}
```

### 14. Statistiques du matching

**GET** `/matching/statistics`

Obtenir les statistiques détaillées du système de matching.

```bash
curl -X GET "http://127.0.0.1:8000/api/matching/statistics?period=month&user_type=sender" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `period` (enum, optionnel) - "today", "week", "month", "quarter", "year" (défaut: "month")
- `user_type` (enum, optionnel) - "sender", "transporter", "both"

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "overview": {
            "total_matches": 245,
            "pending_matches": 45,
            "accepted_matches": 156,
            "completed_matches": 132,
            "rejected_matches": 44
        },
        "success_rates": {
            "acceptance_rate": 63.67,
            "completion_rate": 53.88,
            "rejection_rate": 17.96,
            "shipment_match_rate": 78.50
        },
        "trends": [
            {
                "date": "2026-01-17",
                "total_matches": 15,
                "accepted": 8,
                "completed": 6,
                "avg_score": 72.30
            }
        ]
    }
}
```

### 15. Métriques de performance

**GET** `/matching/performance`

Obtenir les métriques détaillées de performance du système.

```bash
curl -X GET "http://127.0.0.1:8000/api/matching/performance?metric=response_time" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `metric` (enum, optionnel) - "response_time", "accuracy", "user_satisfaction", "geographic_distribution", "overall"

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "metric": "response_time",
        "performance": {
            "avg_match_creation_seconds": 2.3,
            "avg_suggestion_generation_seconds": 1.8,
            "avg_user_response_hours": 4.2,
            "system_uptime_percentage": 99.9
        },
        "last_updated": "2026-01-17T14:30:00Z"
    }
}
```

### 16. Soumettre un feedback

**POST** `/matching/feedback`

Soumettre un feedback sur l'algorithme de matching.

```bash
curl -X POST http://127.0.0.1:8000/api/matching/feedback \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "match_id": 15,
    "rating": 4,
    "feedback_type": "relevance",
    "comment": "Les suggestions étaient très pertinentes",
    "suggested_improvements": ["Améliorer le calcul de distance", "Plus de critères de filtrage"]
  }'
```

**Champs requis :**
- `match_id` (int) - ID de la correspondance
- `rating` (int) - Note de 1 à 5
- `feedback_type` (enum) - "accuracy", "relevance", "speed", "overall"

**Champs optionnels :**
- `comment` (string, max: 1000) - Commentaire détaillé
- `suggested_improvements` (array) - Liste d'améliorations suggérées

**Réponse (201 Created) :**
```json
{
    "success": true,
    "message": "Feedback submitted successfully",
    "data": {
        "feedback_id": "feedback_match_15_42",
        "submitted_at": "2026-01-17T14:45:00Z"
    }
}
```

---

## 🎨 Stratégie d'Intégration Front-End (Home Page)

Pour offrir une expérience utilisateur fluide et engageante sur la page d'accueil, voici les recommandations d'intégration.

### 1. Dashboard des Matchs Actifs
Affichez une section "Mes Matchs en cours" dès la connexion.
- **Endpoint :** `GET /matches/my?status=pending,accepted&limit=3`
- **Composant :** Cartes horizontales (Match Cards) montrant :
    - Le titre du colis et le trajet associé.
    - Le **Matching Score** sous forme de cercle de progression ou jauge colorée (Vert > 70%, Orange 40-70%, Rouge < 40%).
    - Le statut actuel avec un badge distinctif.
    - Le prix proposé mis en avant.

### 2. Système de Notifications & Badge de Chat
Ne manquez aucune communication entre l'expéditeur et le transporteur.
- **Logique :** Appelez `GET /matches/{id}/messages/unread-count` pour chaque match actif.
- **UI :** Une pastille rouge sur l'icône de message de chaque carte de match sur la home page.
- **Notification Bell :** Un résumé global des messages non lus dans le header de l'application.

### 3. Suggestions "Top Matches"
Proposez proactivement des opportunités.
- **Logique :** Récupérez les 3 meilleures suggestions basées sur les derniers shipments/routes de l'utilisateur.
- **Endpoint :** `GET /matches/suggestions?limit=3`
- **UX :** "Nous avons trouvé 3 transporteurs qui correspondent à votre trajet Paris-Lyon !" avec un bouton d'action rapide "Voir l'offre".

---

## 💡 Idées pour une Gestion Optimisée

### Gestion des États (State Management)
- **Polling vs WebSockets :** En l'absence de WebSockets actifs, implémentez un "Long Polling" (toutes les 30-60 secondes) sur l'endpoint des messages non lus pour simuler du temps réel sans surcharger le serveur.
- **Optimistic UI :** Lors de l'envoi d'un message ou de l'acceptation d'un match, mettez à jour l'interface instantanément avant d'attendre la confirmation du serveur pour une sensation de rapidité.

### UX & Micro-interactions
- **Quick Actions :** Permettez d'accepter ou de rejeter une offre directement depuis la home page via un "Swipe" (sur mobile) ou des boutons d'action rapide sur la carte, sans forcer l'utilisateur à entrer dans les détails.
- **Empty States :** Si aucun match n'est présent, remplacez la section par un call-to-action encourageant : "Vous n'avez pas encore de propositions ? Publiez une nouvelle annonce pour commencer !".
- **Filtres Intelligents :** Sur la page dédiée aux matchs, proposez des filtres par "Urgence" ou "Score de Matching" plutôt que par simple date.

---

## Codes d'erreur

### Erreurs de validation (422)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "proposed_price": ["The proposed price field is required."]
    }
}
```

### Correspondance non trouvée (404)
```json
{
    "success": false,
    "message": "Match not found"
}
```

### Statut non valide (400)
```json
{
    "success": false,
    "message": "Cannot accept match with current status"
}
```

### Accès non autorisé (403)
```json
{
    "success": false,
    "message": "You don't have access to this match"
}
```

---

## Intégration JavaScript

### Exemple d'utilisation basique

```javascript
class ColigoMatchingAPI {
    constructor(baseURL, token) {
        this.baseURL = baseURL;
        this.token = token;
    }

    async getSuggestions(type, id, limit = 5) {
        const response = await fetch(
            `${this.baseURL}/api/matches/suggestions?type=${type}&id=${id}&limit=${limit}`,
            {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Accept': 'application/json'
                }
            }
        );
        return response.json();
    }

    async createMatch(matchData) {
        const response = await fetch(`${this.baseURL}/api/matches`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(matchData)
        });
        return response.json();
    }

    async acceptMatch(matchId, finalPrice, response) {
        const result = await fetch(`${this.baseURL}/api/matches/${matchId}/accept`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                final_price: finalPrice,
                sender_response: response
            })
        });
        return result.json();
    }

    async sendMessage(matchId, message) {
        const response = await fetch(`${this.baseURL}/api/matches/${matchId}/messages`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                message_type: 'text'
            })
        });
        return response.json();
    }
}

// Utilisation
const api = new ColigoMatchingAPI('http://127.0.0.1:8000', 'votre-token');

// Obtenir des suggestions pour un shipment
api.getSuggestions('shipment', 1)
    .then(data => console.log(data.data.suggestions));

// Créer une correspondance
api.createMatch({
    shipment_id: 1,
    route_id: 5,
    proposed_price: 150.00,
    pickup_datetime: '2026-01-20T10:00:00Z',
    delivery_datetime: '2026-01-20T18:00:00Z'
}).then(data => console.log(data.data.match));
```

---

## Documentation Swagger

La documentation interactive complète est disponible à l'adresse :
```
http://127.0.0.1:8000/api/documentation
```

Cette interface permet de tester tous les endpoints directement depuis le navigateur.

---

## Notes techniques

### Algorithme de matching
- **Distance** (40%) : Calcul basé sur la formule de Haversine
- **Compatibilité temporelle** (30%) : Écart entre dates souhaitées et disponibles
- **Capacité** (20%) : Ratio poids/capacité disponible
- **Prix** (10%) : Comparaison prix proposé/budget maximum

### Sécurité
- Authentification obligatoire via Bearer token
- Validation stricte des données d'entrée
- Contrôle d'accès : utilisateurs limités à leurs propres correspondances
- Protection contre l'injection SQL et XSS

### Performance
- Cache des statistiques (5 minutes)
- Pagination automatique des résultats
- Indexation optimisée des requêtes fréquentes
- Limitation du taux de requêtes par utilisateur