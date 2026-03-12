# Analyse Fonctionnelle - LOT 7 : Suivi de Livraison

Ce document définit les exigences pour le suivi des colis, de la prise en charge à la livraison finale, incluant le suivi GPS et les preuves de livraison.

## 1. États du Colis (Shipment Lifecycle)
Le système doit gérer une machine à états stricte pour chaque colis :
- **En attente (Matched) :** Le transporteur a été choisi, mais le colis n'est pas encore récupéré.
- **En transit (In Transit) :** Le transporteur a confirmé la prise en charge du colis.
- **Livré (Delivered) :** Le colis est arrivé à destination et la réception est confirmée.
- **Litige (Disputed) :** Un problème a été signalé lors de la livraison.

---

## 2. Liste des User Stories (US)

### A. Suivi en temps réel (Expéditeur)
*   **US-023 : Suivi GPS sur carte**
    *   *Action :* Une carte affiche la position actuelle du transporteur.
    *   *Détails :* Mise à jour toutes les 15 minutes minimum, tracé du trajet parcouru, estimation de l'heure d'arrivée (ETA).
*   **US-024 : Notifications de statut**
    *   *Alertes automatiques :* Colis récupéré, voyage commencé, proximité de la destination (< 50 km), colis livré.

### B. Prise en charge et Livraison
*   **US-026 : Confirmation de réception (Expéditeur)**
    *   *Action :* Bouton "Confirmer la réception" pour libérer le paiement.
    *   *Détails :* Génération automatique d'un reçu de livraison.
*   **US-028 : Validation par Signature (Expéditeur)**
    *   *Action :* Zone de signature tactile sur l'application mobile.
    *   *Détails :* Signature horodatée et géolocalisée, intégrée au reçu final.
*   **Preuve par Photo (Transporteur)**
    *   *Action :* Le transporteur doit pouvoir uploader une photo du colis au point de livraison comme preuve de dépôt.

### C. Gestion des incidents
*   **US-027 : Signalement de litige**
    *   *Action :* Bouton "Signaler un problème" accessible jusqu'à 48h après la livraison.
    *   *Détails :* Sélection du type de problème (dommage, manquant), ajout de photos comme preuves, blocage automatique du paiement.

### D. Historique
*   **US-025 & US-032 : Historique détaillé**
    *   *Action :* Vue chronologique de tous les mouvements et changements de statuts.
    *   *Détails :* Export possible en PDF (reçus et historique).

---

## 3. Analyse Technique Préliminaire

### Backend (Laravel)
1.  **Table `shipment_tracking` :** Pour stocker les coordonnées GPS historiques.
2.  **Table `delivery_proofs` :** Pour stocker les signatures (base64 ou fichier) et les photos de preuve.
3.  **WebSockets :** Pour pousser la nouvelle position GPS du transporteur vers l'application de l'expéditeur sans rafraîchir la page.
4.  **Notifications (FCM) :** Pour les alertes de changement de statut.

### Mobile (Flutter)
1.  **Background Location :** Le transporteur doit envoyer sa position même si l'application est en arrière-plan (avec autorisation utilisateur).
2.  **Signature Pad :** Intégration d'un plugin de dessin pour la signature tactile.
3.  **Appareil Photo :** Module de capture pour les preuves de livraison.

---

## 4. Points de vigilance
- **Confidentialité :** La position GPS du transporteur ne doit être partagée avec l'expéditeur que pendant la durée "En transit".
- **Précision :** L'estimation du temps restant (ETA) doit être recalculée périodiquement.
- **Sécurité :** Le paiement ne doit être libéré que si la signature ET la confirmation expéditeur sont présentes (ou après 48h sans litige).
