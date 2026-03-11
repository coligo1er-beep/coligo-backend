# LOT 5 - Système de Messagerie Intégrée
## Guide d'intégration API & WebSockets

### Vue d'ensemble

L'API LOT 5 implémente un système de messagerie complet permettant aux utilisateurs de communiquer en temps réel. Le système supporte les discussions initiées directement depuis des annonces (Colis ou Trajets) ou depuis des propositions validées (Matchs). Il inclut le support multimédia (photos, audio), les indicateurs de lecture et la gestion de la sécurité (blocage).

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentification
Tous les endpoints nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## 1. Gestion des Conversations

### Lister les discussions
**GET** `/conversations`

Récupère la liste des discussions actives de l'utilisateur, triées par date de dernier message.

**Réponse (200 OK) :**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "type": "match",
                "source_id": 15,
                "unread_count": 3,
                "other_participant": {
                    "id": 2,
                    "first_name": "Marc",
                    "profile_photo": "..."
                },
                "latest_message": {
                    "message": "Bonjour, je suis disponible !",
                    "created_at": "2026-03-11T11:20:05.000000Z"
                }
            }
        ],
        "pagination": { ... }
    }
}
```

### Initialiser une discussion
**POST** `/conversations/start`

Démarre une conversation depuis un contexte spécifique. Si une discussion existe déjà pour ce contexte entre les deux parties, elle est simplement retournée.

**Paramètres :**
- `type` (string, requis) : `shipment`, `route` ou `match`.
- `source_id` (int, requis) : ID de la ressource correspondante.
- `message` (string, optionnel) : Premier message à envoyer.

---

## 2. Échanges de Messages

### Historique des messages
**GET** `/conversations/{id}/messages`

Récupère les messages d'une discussion (pagination par 30 messages).

### Envoyer un message
**POST** `/conversations/{id}/messages`

Supporte le texte et les fichiers multimédias.

**Champs requis :**
- `message_type` (enum) : `text`, `image`, `audio`, `location`.
- `message` (string) : Requis si type `text` ou `location`.
- `attachment` (file) : Requis si type `image` ou `audio`. Max 5MB.

### Marquer comme lu
**PUT** `/conversations/{id}/read`

Marque tous les messages reçus dans cette discussion comme lus.

---

## 3. Sécurité & Blocage

### Bloquer un utilisateur
**POST** `/api/users/block`
- `blocked_id` : ID de l'utilisateur à bloquer.
- `reason` : Motif optionnel.

### Lister les blocages
**GET** `/api/users/blocks`

---

## 4. ⚡ Temps Réel (WebSockets)

Le backend utilise Laravel Broadcasting avec le serveur intégré.

### Paramètres de Connexion (Pusher Client)
| Paramètre | Valeur |
| :--- | :--- |
| **Host** | `192.168.1.140` (Local) ou `31.97.68.223` (Prod) |
| **Port** | `6001` |
| **Key** | `coligo_app_key` |
| **Cluster** | `mt1` |
| **Encrypted** | `false` (Non-SSL) |
| **Scheme** | `http` |

### Channels & Événements
1.  **Channel Privé** : `private-chat.{conversation_id}`
    - **Événement** : `message.sent`
    - **Payload** : Objet `message` complet avec relation `sender`.
    - **Note** : Flutter doit envoyer le Bearer token dans les headers `auth` pour s'abonner.

2.  **Channel Privé** : `private-user.{user_id}`
    - **Usage** : Notifications globales hors du chat actif.

---

## 🖼️ Gestion des Médias (Photos & Audio)

Tous les fichiers envoyés sont stockés dans le disque `public`.

### URLs des fichiers
Pour afficher une image ou lire un audio envoyé, préfixez le `attachment_path` reçu dans l'objet message par l'URL de stockage :
- **Local** : `http://192.168.1.140:8000/storage/{attachment_path}`
- **Prod** : `http://31.97.68.223/storage/{attachment_path}`

### Types de fichiers supportés
- **Images** : `jpg, jpeg, png`
- **Audio** : `mp3, wav, m4a`

---

## 🎨 Conseils d'Intégration Front-End

### Expérience Utilisateur (UX)
- **États de chargement** : Affichez un squelette (skeleton) pendant le chargement initial de l'historique.
- **Optimistic UI** : Affichez le message dans la liste dès que l'utilisateur clique sur envoyer, avec un statut "Envoi en cours", puis confirmez une fois l'API répondue.
- **Gestion des photos** : Utilisez un cache d'image (ex: `cached_network_image` en Flutter) pour ne pas recharger les photos à chaque scrolll.

### Performance
- **Pagination inversée** : Chargez les messages du plus récent au plus ancien. Déclenchez le chargement de la page suivante lorsque l'utilisateur scrolle vers le haut.
- **Réduction des données** : N'appelez l'API `/read` que lorsque la fenêtre de chat est active et visible au premier plan.

---

## Codes d'erreur spécifiques
- `403 Unauthorized` : Tentative d'accès à une discussion dont vous n'êtes pas participant.
- `422 Validation Errors` : Type de fichier non supporté ou message trop long (>1000 car.).
- `400 Bad Request` : Tentative de bloquer son propre comptev.
