# Analyse Fonctionnelle Complète - LOT 5 : Messagerie Intégrée

Ce document définit les exigences pour le système de messagerie, incluant les prises de contact initiales et les discussions liées aux contrats (Matchs).

## 1. Points d'Entrée de la Communication
Le système doit permettre d'initier une discussion depuis trois contextes différents :

1.  **Depuis un Shipment (Colis) :** Un transporteur voit un colis et clique sur "Contacter l'expéditeur".
2.  **Depuis une Route (Trajet) :** Un expéditeur voit un trajet et clique sur "Contacter le voyageur".
3.  **Depuis un Match (Contrat) :** Une fois la proposition créée, la discussion continue pour finaliser l'enlèvement.

---

## 2. User Stories (US) - Enrichies

### A. Initiation des Échanges
*   **US-018bis : Contact direct depuis une annonce**
    *   *Action :* Sur chaque détail d'annonce (Shipment ou Route), un bouton "Envoyer un message" est présent.
    *   *Logique :* Si une conversation existe déjà entre ces deux utilisateurs pour cette ressource, ouvrir l'existante. Sinon, en créer une nouvelle.
*   **US-018ter : Contexte de l'annonce**
    *   *Détail :* Le premier message d'une conversation initiée depuis une annonce doit automatiquement inclure une référence à l'annonce (ex: "Demande d'info pour votre colis 'iPhone 15'").

### B. Fonctionnalités du Chat (Temps Réel)
*   **US-072 : Fluidité & Indicateurs**
    *   Latence < 2s, indicateur "En train d'écrire", statut de lecture (vu/non vu) avec double coche.
*   **US-020 : Médias (Photos)**
    *   Envoi direct de photos (Galerie/Appareil). Max 5 Mo.
*   **US-073 : Audio (Vocaux)**
    *   Messages vocaux jusqu'à 2 minutes avec lecteur intégré.
*   **US-074 : Position**
    *   Partage de position GPS en temps réel (durée limitée) pour le rendez-vous.

### C. Gestion & Sécurité
*   **US-021 : Centre de Messagerie**
    *   Une vue regroupant toutes les discussions, triées par date décroissante.
    *   Filtres : "En attente de match", "Matchs actifs", "Archivés".
*   **US-022 : Modération**
    *   Blocage d'utilisateur et signalement de messages inappropriés.

---

## 3. Règles Métier
1.  **Unique Conversation :** Il ne peut y avoir qu'une seule conversation active par couple (Utilisateur A, Utilisateur B) pour une ressource donnée (Shipment X ou Route Y).
2.  **Transition vers Match :** Une discussion initiée sur un Shipment peut "évoluer" en Match formel sans changer de fenêtre de chat.
3.  **Notifications :** Chaque message génère une notification push si l'utilisateur n'est pas actif dans le chat.
