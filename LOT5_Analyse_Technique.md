# Analyse Technique Complète - LOT 5 : Messagerie Intégrée

## 1. Nouveau Schéma de Base de Données

Pour supporter le contact direct hors-match, nous utilisons une table `conversations` comme conteneur.

### Table `conversations`
- `id` (bigint, PK)
- `type` (enum) : `shipment`, `route`, `match`.
- `source_id` (bigint) : ID de l'annonce ou du match concerné.
- `participant_1_id` (foreignId) : Initiateur.
- `participant_2_id` (foreignId) : Destinataire.
- `last_message_at` (timestamp) : Pour le tri de la liste.
- `is_archived` (boolean)
- `created_at` / `updated_at`

### Table `messages` (Mise à jour)
- `id` (bigint, PK)
- `conversation_id` (foreignId) : Référence à la table ci-dessus.
- `sender_id` (foreignId)
- `message` (text)
- `message_type` (enum) : `text`, `image`, `audio`, `location`, `system`.
- `attachment_path` (string, nullable)
- `read_at` (timestamp, nullable)
- `created_at` / `updated_at`

---

## 2. API Endpoints (Mise à jour)

| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| **GET** | `/conversations` | Liste des discussions de l'utilisateur (Dashboard). |
| **POST** | `/conversations/start` | Initier une discussion depuis un Shipment/Route (si non existante). |
| **GET** | `/conversations/{id}/messages` | Historique des messages. |
| **POST** | `/conversations/{id}/messages` | Envoyer un message (Texte/Fichier). |
| **PUT** | `/messages/{id}/read` | Marquer un message comme lu. |
| **POST** | `/users/block` | Bloquer un interlocuteur. |

---

## 3. WebSockets (Logique de Channel)

### Channels
- `private-chat.{conversation_id}` : Échanges en temps réel dans la bulle.
- `private-user.{user_id}` : Événements globaux (notification de nouvelle conversation, mise à jour de la liste).

### Événements
- `MessageSent` : Contenu du message + métadonnées.
- `TypingIndicator` : Pour le "est en train d'écrire...".
- `ConversationUpdated` : Pour remonter une discussion en haut de la liste sur le Dashboard.

---

## 4. Transition Discussion ➔ Match (Critique)

Lorsqu'un utilisateur décide de créer un Match alors qu'une discussion est déjà ouverte sur un Shipment :
1.  Le système crée l'entrée dans la table `matches`.
2.  Le système met à jour le `type` de la conversation à `match` et change le `source_id` pour le nouvel ID du match.
3.  Un message système est inséré : *"Une proposition de transport a été créée. [Voir l'offre]"*.
