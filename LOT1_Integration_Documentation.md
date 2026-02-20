# LOT 1 - Authentification et Gestion des Profils
## Guide d'intégration API

### Vue d'ensemble

L'API LOT 1 implémente le système complet d'authentification et de gestion des profils pour la plateforme Coligo. Elle inclut l'inscription, la connexion, la vérification OTP, la gestion des documents d'identité et la mise à jour des profils utilisateurs.

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentification
La plupart des endpoints nécessitent une authentification Bearer token (sauf inscription, connexion et OTP).

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Inscription d'un utilisateur

**POST** `/auth/register`

Créer un nouveau compte utilisateur avec email et téléphone uniques.

```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "first_name": "Jean",
    "last_name": "Dupont",
    "email": "jean.dupont@example.com",
    "phone": "+33123456789",
    "password": "password123",
    "password_confirmation": "password123",
    "user_type": "sender"
  }'
```

**Champs requis :**
- `first_name` (string) - Prénom
- `last_name` (string) - Nom de famille
- `email` (string) - Email unique
- `phone` (string) - Téléphone unique
- `password` (string) - Mot de passe (min 8 caractères)
- `password_confirmation` (string) - Confirmation du mot de passe
- `user_type` (enum) - Type d'utilisateur : "sender", "transporter", "both"

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "jean.dupont@example.com",
      "phone": "+33123456789",
      "email_verified_at": null,
      "phone_verified_at": null,
      "first_name": "Jean",
      "last_name": "Dupont",
      "date_of_birth": null,
      "gender": null,
      "profile_photo": null,
      "user_type": "sender",
      "status": "active",
      "address_street": null,
      "address_city": null,
      "address_postal_code": null,
      "address_country": null,
      "latitude": null,
      "longitude": null,
      "is_verified": false,
      "verification_score": 0,
      "created_at": "2026-01-17T11:02:32.000000Z",
      "updated_at": "2026-01-17T11:02:32.000000Z"
    },
    "token": "1|abc123def456ghi789",
    "token_type": "Bearer"
  }
}
```

**Erreurs possibles :**
- `422` - Erreur de validation (email/téléphone déjà utilisé, mots de passe non identiques, etc.)

---

### 2. Connexion utilisateur

**POST** `/auth/login`

Authentifier un utilisateur avec email/téléphone et mot de passe.

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "password": "password123"
  }'
```

**Champs requis (au choix) :**
- `email` (string) - Email OU téléphone requis
- `phone` (string) - Téléphone OU email requis
- `password` (string) - Mot de passe

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "email": "jean.dupont@example.com",
      "phone": "+33123456789",
      "first_name": "Jean",
      "last_name": "Dupont",
      "user_type": "sender",
      "status": "active",
      "is_verified": false,
      "verification_score": 0,
      "created_at": "2026-01-17T11:02:32.000000Z",
      "updated_at": "2026-01-17T11:02:32.000000Z"
    },
    "token": "2|xyz789uvw456rst123",
    "token_type": "Bearer"
  }
}
```

**Erreurs possibles :**
- `401` - Identifiants invalides
- `403` - Compte suspendu
- `422` - Erreur de validation

---

### 3. Déconnexion

**POST** `/auth/logout`

Déconnecter l'utilisateur et supprimer le token actuel.

```bash
curl -X POST http://127.0.0.1:8000/api/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 4. Rafraîchir le token

**POST** `/auth/refresh`

Générer un nouveau token d'authentification.

```bash
curl -X POST http://127.0.0.1:8000/api/auth/refresh \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "3|new789token456here123",
    "token_type": "Bearer"
  }
}
```

---

### 5. Envoyer un code OTP

**POST** `/otp/send`

Envoyer un code de vérification à 6 chiffres par email ou SMS.

```bash
curl -X POST http://127.0.0.1:8000/api/otp/send \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "type": "email"
  }'
```

**Champs requis :**
- `type` (enum) - "email" ou "phone"
- `email` (string) - Requis si type="email"
- `phone` (string) - Requis si type="phone"

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "data": {
    "type": "email",
    "expires_in": 600
  }
}
```

**Erreurs possibles :**
- `404` - Utilisateur non trouvé
- `429` - OTP déjà envoyé (cooldown actif)

---

### 6. Vérifier un code OTP

**POST** `/otp/verify`

Vérifier un code OTP et marquer email/téléphone comme vérifié.

```bash
curl -X POST http://127.0.0.1:8000/api/otp/verify \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "type": "email",
    "code": "123456"
  }'
```

**Champs requis :**
- `type` (enum) - "email" ou "phone"
- `code` (string) - Code à 6 chiffres
- `email` (string) - Requis si type="email"
- `phone` (string) - Requis si type="phone"

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "OTP verified successfully",
  "data": {
    "verified": true,
    "verification_type": "email",
    "verification_score": 25,
    "is_verified": false
  }
}
```

**Erreurs possibles :**
- `400` - Code invalide ou expiré

---

### 7. Consulter le profil

**GET** `/profile`

Récupérer les informations complètes du profil utilisateur connecté.

```bash
curl -X GET http://127.0.0.1:8000/api/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "jean.dupont@example.com",
      "phone": "+33123456789",
      "email_verified_at": "2026-01-17T11:30:00.000000Z",
      "phone_verified_at": null,
      "first_name": "Jean",
      "last_name": "Dupont",
      "date_of_birth": "1990-05-15",
      "gender": "male",
      "profile_photo": "/storage/profiles/user_1_photo.jpg",
      "user_type": "sender",
      "status": "active",
      "address_street": "123 Rue de la Paix",
      "address_city": "Paris",
      "address_postal_code": "75001",
      "address_country": "France",
      "latitude": 48.8566,
      "longitude": 2.3522,
      "is_verified": false,
      "verification_score": 25,
      "created_at": "2026-01-17T11:02:32.000000Z",
      "updated_at": "2026-01-17T11:25:00.000000Z"
    }
  }
}
```

---

### 8. Mettre à jour le profil

**PUT** `/profile`

Modifier les informations du profil utilisateur.

```bash
curl -X PUT http://127.0.0.1:8000/api/profile \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "first_name": "Jean-Michel",
    "last_name": "Dupont",
    "date_of_birth": "1990-05-15",
    "gender": "male",
    "address_street": "123 Rue de la Paix",
    "address_city": "Paris",
    "latitude": 48.8566,
    "longitude": 2.3522
  }'
```

**Champs optionnels :**
- `first_name` (string) - Prénom
- `last_name` (string) - Nom
- `date_of_birth` (date) - Date de naissance
- `gender` (enum) - "male", "female", "other"
- `address_street` (string) - Adresse
- `address_city` (string) - Ville
- `latitude` (float) - Coordonnée GPS latitude
- `longitude` (float) - Coordonnée GPS longitude

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Jean-Michel",
      "last_name": "Dupont",
      "date_of_birth": "1990-05-15",
      "gender": "male",
      "address_street": "123 Rue de la Paix",
      "address_city": "Paris",
      "latitude": 48.8566,
      "longitude": 2.3522,
      "updated_at": "2026-01-17T11:35:00.000000Z"
    }
  }
}
```

**Erreurs possibles :**
- `422` - Erreur de validation

---

### 9. Upload photo de profil

**POST** `/profile/photo`

Télécharger une photo de profil (remplace l'ancienne si existante).

```bash
curl -X POST http://127.0.0.1:8000/api/profile/photo \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -F "photo=@/path/to/photo.jpg"
```

**Champs requis :**
- `photo` (file) - Image (JPG, PNG, max 5MB)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Profile photo uploaded successfully",
  "data": {
    "photo_url": "/storage/profiles/user_1_1642425600.jpg"
  }
}
```

**Erreurs possibles :**
- `422` - Format de fichier invalide ou taille dépassée

---

### 10. Supprimer photo de profil

**DELETE** `/profile/photo`

Supprimer la photo de profil actuelle.

```bash
curl -X DELETE http://127.0.0.1:8000/api/profile/photo \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Profile photo deleted successfully"
}
```

---

### 11. Statut de vérification

**GET** `/profile/verification`

Consulter le statut de vérification et les étapes complétées.

```bash
curl -X GET http://127.0.0.1:8000/api/profile/verification \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Verification status retrieved successfully",
  "data": {
    "is_verified": false,
    "verification_score": 50,
    "required_score": 75,
    "steps": {
      "email_verified": {
        "completed": true,
        "points": 25,
        "verified_at": "2026-01-17T11:30:00.000000Z"
      },
      "phone_verified": {
        "completed": false,
        "points": 25,
        "verified_at": null
      },
      "documents_verified": {
        "completed": true,
        "points": 50,
        "documents_count": 2,
        "verified_documents": ["id_card", "passport"]
      }
    }
  }
}
```

---

### 12. Lister les documents

**GET** `/profile/documents`

Récupérer la liste de tous les documents d'identité de l'utilisateur.

```bash
curl -X GET http://127.0.0.1:8000/api/profile/documents \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Documents retrieved successfully",
  "data": {
    "documents": [
      {
        "id": 1,
        "user_id": 1,
        "document_type": "id_card",
        "document_number": "123456789",
        "document_file_path": "documents/1_id_card_1642425600.pdf",
        "expiration_date": "2030-12-31",
        "verification_status": "verified",
        "verified_at": "2026-01-17T11:40:00.000000Z",
        "verified_by": 1,
        "created_at": "2026-01-17T11:20:00.000000Z",
        "updated_at": "2026-01-17T11:40:00.000000Z"
      },
      {
        "id": 2,
        "user_id": 1,
        "document_type": "passport",
        "document_number": "AB1234567",
        "document_file_path": "documents/1_passport_1642425700.jpg",
        "expiration_date": "2032-06-15",
        "verification_status": "pending",
        "verified_at": null,
        "verified_by": null,
        "created_at": "2026-01-17T11:25:00.000000Z",
        "updated_at": "2026-01-17T11:25:00.000000Z"
      }
    ]
  }
}
```

---

### 13. Upload document

**POST** `/profile/documents`

Télécharger un nouveau document d'identité.

```bash
curl -X POST http://127.0.0.1:8000/api/profile/documents \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -F "document_type=id_card" \
  -F "document_number=123456789" \
  -F "document_file=@/path/to/document.pdf" \
  -F "expiration_date=2030-12-31"
```

**Champs requis :**
- `document_type` (enum) - "id_card", "passport", "driving_license", "other"
- `document_file` (file) - Document (PDF, JPG, PNG, max 5MB)

**Champs optionnels :**
- `document_number` (string) - Numéro du document
- `expiration_date` (date) - Date d'expiration (future)

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "document": {
      "id": 3,
      "user_id": 1,
      "document_type": "id_card",
      "document_number": "123456789",
      "document_file_path": "documents/1_id_card_1642425800.pdf",
      "expiration_date": "2030-12-31",
      "verification_status": "pending",
      "verified_at": null,
      "verified_by": null,
      "created_at": "2026-01-17T11:30:00.000000Z",
      "updated_at": "2026-01-17T11:30:00.000000Z"
    },
    "file_url": "/storage/documents/1_id_card_1642425800.pdf"
  }
}
```

**Erreurs possibles :**
- `409` - Document de ce type déjà existant
- `422` - Erreur de validation (format, taille, etc.)

---

### 14. Consulter un document

**GET** `/profile/documents/{id}`

Récupérer les détails d'un document spécifique.

```bash
curl -X GET http://127.0.0.1:8000/api/profile/documents/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document retrieved successfully",
  "data": {
    "document": {
      "id": 1,
      "user_id": 1,
      "document_type": "id_card",
      "document_number": "123456789",
      "document_file_path": "documents/1_id_card_1642425600.pdf",
      "expiration_date": "2030-12-31",
      "verification_status": "verified",
      "verified_at": "2026-01-17T11:40:00.000000Z",
      "verified_by": 1,
      "created_at": "2026-01-17T11:20:00.000000Z",
      "updated_at": "2026-01-17T11:40:00.000000Z"
    },
    "file_url": "/storage/documents/1_id_card_1642425600.pdf"
  }
}
```

**Erreurs possibles :**
- `404` - Document non trouvé

---

### 15. Modifier un document

**PUT** `/profile/documents/{id}`

Mettre à jour les métadonnées ou le fichier d'un document.

```bash
curl -X PUT http://127.0.0.1:8000/api/profile/documents/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}" \
  -F "document_number=987654321" \
  -F "expiration_date=2032-12-31" \
  -F "document_file=@/path/to/new_document.pdf"
```

**Champs optionnels :**
- `document_number` (string) - Nouveau numéro
- `expiration_date` (date) - Nouvelle date d'expiration
- `document_file` (file) - Nouveau fichier (remplace l'ancien)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document updated successfully",
  "data": {
    "document": {
      "id": 1,
      "user_id": 1,
      "document_type": "id_card",
      "document_number": "987654321",
      "document_file_path": "documents/1_id_card_1642425900.pdf",
      "expiration_date": "2032-12-31",
      "verification_status": "pending",
      "verified_at": null,
      "verified_by": null,
      "created_at": "2026-01-17T11:20:00.000000Z",
      "updated_at": "2026-01-17T11:45:00.000000Z"
    },
    "file_url": "/storage/documents/1_id_card_1642425900.pdf"
  }
}
```

**Erreurs possibles :**
- `403` - Document déjà vérifié (modification interdite)
- `404` - Document non trouvé
- `422` - Erreur de validation

---

### 16. Supprimer un document

**DELETE** `/profile/documents/{id}`

Supprimer définitivement un document et son fichier.

```bash
curl -X DELETE http://127.0.0.1:8000/api/profile/documents/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document deleted successfully"
}
```

**Erreurs possibles :**
- `404` - Document non trouvé

---

## Règles métier importantes

### Score de vérification
- **Email vérifié** : +25 points (mis à jour automatiquement après vérification OTP)
- **Téléphone vérifié** : +25 points (mis à jour automatiquement après vérification OTP)
- **Documents vérifiés** : +50 points (mis à jour automatiquement après validation admin)
- **Seuil de vérification** : 75 points minimum
- **Statut automatique** : `is_verified = true` si score >= 75
- **Recalcul automatique** : Le score et le statut sont recalculés après chaque vérification OTP ou modification de documents

### Types de documents
- **id_card** - Carte d'identité
- **passport** - Passeport
- **driving_license** - Permis de conduire
- **other** - Autre document

### Statuts de vérification documents
- **pending** - En attente de vérification
- **verified** - Vérifié par un administrateur
- **rejected** - Rejeté (nécessite re-soumission)

### Gestion des fichiers
- **Formats supportés** : PDF, JPG, JPEG, PNG
- **Taille maximum** : 5MB par fichier
- **Stockage** : `/storage/public/` avec organisation par dossiers
- **Nettoyage automatique** : Suppression des anciens fichiers lors du remplacement

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `401` - Non authentifié (token manquant/invalide)
- `403` - Accès interdit (compte suspendu, action non autorisée)
- `404` - Ressource non trouvée
- `409` - Conflit (document déjà existant)
- `422` - Erreur de validation
- `429` - Trop de tentatives (cooldown OTP)
- `500` - Erreur serveur

---

## Exemples d'erreurs de validation

### Inscription - Email déjà utilisé :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": ["The email has already been taken."],
    "phone": ["The phone has already been taken."]
  }
}
```

### Mot de passe invalide :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "password": ["The password must be at least 8 characters."],
    "password_confirmation": ["The password confirmation does not match."]
  }
}
```

### Document déjà existant :
```json
{
  "success": false,
  "message": "Document of this type already exists. Please update the existing one."
}
```

### Fichier invalide :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "document_file": ["The document file must be a file of type: pdf, jpg, jpeg, png."],
    "document_file": ["The document file may not be greater than 5120 kilobytes."]
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
- Visualiser les réponses d'exemple

---

**Version API** : 1.0.0
**Framework** : Laravel 8.x
**Authentification** : Laravel Sanctum
**Documentation** : OpenAPI 3.0