# ANALYSE API COLIGO BACKEND - Plateforme de Transport Collaboratif

## 🎯 Vue d'ensemble du Projet

Plateforme de mise en relation entre expéditeurs de colis et transporteurs avec géolocalisation et système de matching intelligent.

## 📊 Entités Principales Identifiées

### 1. **Users** (Utilisateurs)
- **Types** : Expéditeur, Transporteur (ou les deux)
- **Authentification** : Email + Téléphone avec OTP
- **Profils** : Informations personnelles, documents d'identité

### 2. **Shipments** (Annonces de Colis)
- **Contenu** : Description, photos, poids, dimensions
- **Géolocalisation** : Point de départ et d'arrivée
- **Budget** : Prix proposé par l'expéditeur

### 3. **Routes** (Trajets Transporteurs)
- **Itinéraire** : Départ, arrivée, étapes intermédiaires
- **Capacité** : Poids disponible, dates
- **Statut** : Actif, complet, terminé

### 4. **Matches** (Mises en Relation)
- **Algorithme** : Compatibilité géographique et temporelle
- **Propositions** : Acceptation/refus

### 5. **Notifications**
- **Types** : Push, SMS, email
- **Événements** : Nouvelles opportunités, statuts, rappels

## 🗄️ STRUCTURES DE DONNÉES

### **Table: users**
```sql
id (bigint, primary key)
email (string, unique)
phone (string, unique)
email_verified_at (timestamp, nullable)
phone_verified_at (timestamp, nullable)
password (string)
first_name (string)
last_name (string)
date_of_birth (date, nullable)
gender (enum: male, female, other, nullable)
profile_photo (string, nullable)
user_type (enum: sender, transporter, both)
status (enum: active, inactive, suspended)
address_street (string, nullable)
address_city (string, nullable)
address_postal_code (string, nullable)
address_country (string, nullable)
latitude (decimal, nullable)
longitude (decimal, nullable)
is_verified (boolean, default: false)
verification_score (integer, default: 0)
created_at (timestamp)
updated_at (timestamp)
```

### **Table: user_documents**
```sql
id (bigint, primary key)
user_id (bigint, foreign key -> users.id)
document_type (enum: id_card, passport, driving_license, other)
document_number (string, nullable)
document_file_path (string)
verification_status (enum: pending, verified, rejected)
verified_at (timestamp, nullable)
verified_by (bigint, nullable, foreign key -> users.id)
expiration_date (date, nullable)
created_at (timestamp)
updated_at (timestamp)
```

### **Table: otp_codes**
```sql
id (bigint, primary key)
user_id (bigint, foreign key -> users.id)
type (enum: phone, email)
code (string)
expires_at (timestamp)
used_at (timestamp, nullable)
attempts (integer, default: 0)
created_at (timestamp)
```

### **Table: shipments**
```sql
id (bigint, primary key)
user_id (bigint, foreign key -> users.id)
title (string)
description (text)
weight (decimal)
length (decimal, nullable)
width (decimal, nullable)
height (decimal, nullable)
fragile (boolean, default: false)
dangerous_goods (boolean, default: false)
pickup_address (string)
pickup_city (string)
pickup_postal_code (string, nullable)
pickup_country (string)
pickup_latitude (decimal)
pickup_longitude (decimal)
pickup_date_from (datetime)
pickup_date_to (datetime)
delivery_address (string)
delivery_city (string)
delivery_postal_code (string, nullable)
delivery_country (string)
delivery_latitude (decimal)
delivery_longitude (decimal)
delivery_date_limit (datetime)
budget_min (decimal)
budget_max (decimal)
currency (string, default: 'EUR')
status (enum: draft, published, matched, in_transit, delivered, cancelled)
priority (enum: low, normal, high, urgent)
special_instructions (text, nullable)
published_at (timestamp, nullable)
created_at (timestamp)
updated_at (timestamp)
```

### **Table: shipment_photos**
```sql
id (bigint, primary key)
shipment_id (bigint, foreign key -> shipments.id)
file_path (string)
file_name (string)
file_size (integer)
mime_type (string)
is_primary (boolean, default: false)
sort_order (integer, default: 0)
created_at (timestamp)
```

### **Table: routes**
```sql
id (bigint, primary key)
user_id (bigint, foreign key -> users.id)
title (string)
description (text, nullable)
departure_address (string)
departure_city (string)
departure_country (string)
departure_latitude (decimal)
departure_longitude (decimal)
departure_date_from (datetime)
departure_date_to (datetime)
arrival_address (string)
arrival_city (string)
arrival_country (string)
arrival_latitude (decimal)
arrival_longitude (decimal)
arrival_date_from (datetime)
arrival_date_to (datetime)
total_capacity_kg (decimal)
available_capacity_kg (decimal)
vehicle_type (enum: car, van, truck, motorcycle, other)
vehicle_description (string, nullable)
price_per_kg (decimal, nullable)
min_shipment_price (decimal, nullable)
status (enum: draft, published, in_progress, completed, cancelled)
recurring (boolean, default: false)
recurring_pattern (json, nullable)
special_conditions (text, nullable)
published_at (timestamp, nullable)
created_at (timestamp)
updated_at (timestamp)
```

### **Table: route_waypoints**
```sql
id (bigint, primary key)
route_id (bigint, foreign key -> routes.id)
address (string)
city (string)
country (string)
latitude (decimal)
longitude (decimal)
stop_order (integer)
estimated_arrival (datetime, nullable)
is_flexible (boolean, default: true)
created_at (timestamp)
```

### **Table: matches**
```sql
id (bigint, primary key)
shipment_id (bigint, foreign key -> shipments.id)
route_id (bigint, foreign key -> routes.id)
transporter_id (bigint, foreign key -> users.id)
sender_id (bigint, foreign key -> users.id)
status (enum: pending, accepted, rejected, completed, cancelled)
proposed_price (decimal)
final_price (decimal, nullable)
pickup_datetime (datetime, nullable)
delivery_datetime (datetime, nullable)
matching_score (decimal, nullable)
distance_km (decimal, nullable)
estimated_duration_hours (decimal, nullable)
transporter_message (text, nullable)
sender_response (text, nullable)
accepted_at (timestamp, nullable)
rejected_at (timestamp, nullable)
completed_at (timestamp, nullable)
created_at (timestamp)
updated_at (timestamp)
```

### **Table: notifications**
```sql
id (bigint, primary key)
user_id (bigint, foreign key -> users.id)
type (enum: new_match, status_update, message, reminder, system)
title (string)
message (text)
data (json, nullable)
channels (json) # ['push', 'sms', 'email']
status (enum: pending, sent, failed, read)
priority (enum: low, normal, high, urgent)
sent_at (timestamp, nullable)
read_at (timestamp, nullable)
related_type (string, nullable) # 'shipment', 'route', 'match'
related_id (bigint, nullable)
created_at (timestamp)
```

## 🔗 API ENDPOINTS PAR LOT

## **LOT 1: Authentification & Gestion des Profils**

### **🔐 Authentication**
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
```

### **📱 OTP Verification**
```
POST   /api/otp/send                 # Envoyer code OTP
POST   /api/otp/verify               # Vérifier code OTP
POST   /api/otp/resend               # Renvoyer code OTP
```

### **👤 User Profile**
```
GET    /api/profile                  # Profil utilisateur actuel
PUT    /api/profile                  # Mettre à jour profil
POST   /api/profile/photo            # Upload photo profil
DELETE /api/profile/photo            # Supprimer photo profil
```

### **📄 Document Management**
```
GET    /api/profile/documents        # Liste documents utilisateur
POST   /api/profile/documents        # Upload nouveau document
PUT    /api/profile/documents/{id}   # Mettre à jour document
DELETE /api/profile/documents/{id}   # Supprimer document
GET    /api/profile/verification     # Statut vérification profil
```

---

## **LOT 2: Gestion des Annonces de Colis**

### **📦 Shipments Management**
```
GET    /api/shipments                # Liste annonces (avec filtres)
POST   /api/shipments                # Créer nouvelle annonce
GET    /api/shipments/{id}           # Détails annonce
PUT    /api/shipments/{id}           # Mettre à jour annonce
DELETE /api/shipments/{id}           # Supprimer annonce
POST   /api/shipments/{id}/publish   # Publier annonce
POST   /api/shipments/{id}/cancel    # Annuler annonce
```

### **📸 Shipment Photos**
```
POST   /api/shipments/{id}/photos    # Upload photos (max 5)
DELETE /api/shipments/{id}/photos/{photoId}  # Supprimer photo
PUT    /api/shipments/{id}/photos/{photoId}/primary  # Définir photo principale
GET    /api/shipments/{id}/photos    # Liste photos annonce
```

### **🔍 Search & Filters**
```
GET    /api/shipments/search         # Recherche avancée
GET    /api/shipments/nearby         # Annonces géolocalisées
GET    /api/shipments/categories     # Catégories de colis
GET    /api/shipments/my             # Mes annonces
```

---

## **LOT 3: Gestion des Trajets Transporteurs**

### **🚛 Routes Management**
```
GET    /api/routes                   # Liste trajets disponibles
POST   /api/routes                   # Créer nouveau trajet
GET    /api/routes/{id}              # Détails trajet
PUT    /api/routes/{id}              # Mettre à jour trajet
DELETE /api/routes/{id}              # Supprimer trajet
POST   /api/routes/{id}/publish      # Publier trajet
POST   /api/routes/{id}/complete     # Marquer terminé
```

### **📍 Route Waypoints**
```
GET    /api/routes/{id}/waypoints    # Points d'arrêt trajet
POST   /api/routes/{id}/waypoints    # Ajouter point d'arrêt
PUT    /api/routes/{id}/waypoints/{waypointId}  # Modifier point
DELETE /api/routes/{id}/waypoints/{waypointId}  # Supprimer point
```

### **🔍 Route Discovery**
```
GET    /api/routes/search            # Recherche trajets
GET    /api/routes/nearby            # Trajets géolocalisés
GET    /api/routes/my                # Mes trajets
GET    /api/routes/{id}/requests     # Demandes sur mon trajet
```

---

## **LOT 4: Système de Matching & Propositions**

### **🎯 Matching System**
```
GET    /api/matches/suggestions      # Suggestions automatiques
POST   /api/matches                  # Créer proposition manuelle
GET    /api/matches/{id}             # Détails mise en relation
PUT    /api/matches/{id}/accept      # Accepter proposition
PUT    /api/matches/{id}/reject      # Rejeter proposition
PUT    /api/matches/{id}/complete    # Marquer terminé
GET    /api/matches/my               # Mes mises en relation
```

### **💬 Match Communication**
```
POST   /api/matches/{id}/messages    # Envoyer message
GET    /api/matches/{id}/messages    # Historique messages
PUT    /api/matches/{id}/messages/{msgId}/read  # Marquer lu
```

### **📊 Matching Analytics**
```
GET    /api/matching/algorithm/config  # Configuration algorithme
GET    /api/matching/statistics       # Statistiques matching
POST   /api/matching/feedback         # Feedback algorithme
```

---

## **LOT 5: Système de Notifications**

### **🔔 Notification Management**
```
GET    /api/notifications            # Liste notifications
GET    /api/notifications/unread     # Non lues seulement
PUT    /api/notifications/{id}/read  # Marquer comme lue
PUT    /api/notifications/mark-all-read  # Tout marquer lu
DELETE /api/notifications/{id}       # Supprimer notification
```

### **⚙️ Notification Settings**
```
GET    /api/notifications/settings   # Préférences notifications
PUT    /api/notifications/settings   # Mettre à jour préférences
POST   /api/notifications/test       # Tester notification
GET    /api/notifications/channels   # Canaux disponibles
```

### **📱 Push Notifications**
```
POST   /api/notifications/devices    # Enregistrer device token
PUT    /api/notifications/devices/{id}  # Mettre à jour token
DELETE /api/notifications/devices/{id}  # Supprimer device
```

---

## **LOT 6: Géolocalisation & Cartographie**

### **🗺️ Geocoding & Maps**
```
GET    /api/geocoding/search         # Recherche adresses
GET    /api/geocoding/reverse        # Géocodage inverse
GET    /api/geocoding/autocomplete   # Auto-complétion adresses
POST   /api/routing/calculate        # Calcul itinéraire
GET    /api/routing/optimize         # Optimisation trajet
```

### **📍 Location Tracking**
```
POST   /api/tracking/location        # Mettre à jour position
GET    /api/tracking/{matchId}       # Suivi en temps réel
GET    /api/tracking/history         # Historique positions
```

---

## **LOT 7: Administration & Analytics**

### **👥 User Management (Admin)**
```
GET    /api/admin/users              # Liste utilisateurs
GET    /api/admin/users/{id}         # Détails utilisateur
PUT    /api/admin/users/{id}/verify  # Vérifier utilisateur
PUT    /api/admin/users/{id}/suspend # Suspendre utilisateur
GET    /api/admin/users/statistics   # Statistiques utilisateurs
```

### **📊 Platform Analytics**
```
GET    /api/admin/analytics/overview # Vue d'ensemble
GET    /api/admin/analytics/shipments  # Analytics colis
GET    /api/admin/analytics/routes   # Analytics trajets
GET    /api/admin/analytics/matches  # Analytics matching
GET    /api/admin/analytics/revenue  # Analytics revenus
```

### **🛠️ System Management**
```
GET    /api/admin/system/health      # Santé système
GET    /api/admin/system/logs        # Logs système
PUT    /api/admin/system/config      # Configuration
POST   /api/admin/system/maintenance # Mode maintenance
```

---

## 🔄 FLUX DE DONNÉES PRINCIPAUX

### **1. Processus d'Inscription**
```
User Registration → Email/Phone Verification → Profile Setup → Document Upload → Verification Review → Account Activation
```

### **2. Processus de Matching**
```
Shipment Creation → Route Discovery → Algorithmic Matching → Proposal Generation → Negotiation → Agreement → Execution → Completion
```

### **3. Processus de Notification**
```
Event Trigger → Notification Generation → Channel Selection → Delivery → Status Tracking → User Interaction
```

---

## 🔧 CONFIGURATIONS TECHNIQUES

### **Authentication & Security**
- **JWT Tokens** : Access + Refresh tokens
- **Rate Limiting** : Par endpoint et par utilisateur
- **CORS** : Configuration pour apps mobiles
- **File Upload** : Validation taille/type, stockage sécurisé

### **Database Indexing**
```sql
-- Users
INDEX idx_users_email (email)
INDEX idx_users_phone (phone)
INDEX idx_users_location (latitude, longitude)

-- Shipments
INDEX idx_shipments_location_pickup (pickup_latitude, pickup_longitude)
INDEX idx_shipments_location_delivery (delivery_latitude, delivery_longitude)
INDEX idx_shipments_dates (pickup_date_from, delivery_date_limit)
INDEX idx_shipments_status (status)

-- Routes
INDEX idx_routes_departure (departure_latitude, departure_longitude)
INDEX idx_routes_arrival (arrival_latitude, arrival_longitude)
INDEX idx_routes_dates (departure_date_from, arrival_date_to)
INDEX idx_routes_capacity (available_capacity_kg)

-- Matches
INDEX idx_matches_shipment (shipment_id)
INDEX idx_matches_route (route_id)
INDEX idx_matches_status (status)
```

### **API Response Format**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "meta": {
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_items": 95
    }
  },
  "errors": null
}
```

---

## ⚠️ CONSIDÉRATIONS IMPORTANTES

### **Sécurité**
- Validation stricte des entrées
- Chiffrement des documents sensibles
- Audit trail pour actions critiques
- Protection contre attaques DDOS

### **Performance**
- Cache Redis pour données fréquentes
- Optimisation requêtes géospatiales
- Pagination systématique
- Compression images automatique

### **Conformité**
- RGPD pour données personnelles
- Chiffrement données sensibles
- Logs d'accès et modifications
- Politique de rétention des données

---

## 🚀 ÉTAPES DE DÉVELOPPEMENT RECOMMANDÉES

1. **Phase 1** : LOT 1 (Auth & Profils) - Base solide
2. **Phase 2** : LOT 2 (Colis) - Fonctionnalité cœur
3. **Phase 3** : LOT 3 (Trajets) - Complément transporteurs
4. **Phase 4** : LOT 4 (Matching) - Intelligence du système
5. **Phase 5** : LOT 5 (Notifications) - Expérience utilisateur
6. **Phase 6** : LOT 6-7 (Geo & Admin) - Fonctionnalités avancées

Cette analyse fournit une base solide pour le développement de l'API Coligo Backend. Chaque endpoint est pensé pour une expérience utilisateur optimale et une architecture scalable.