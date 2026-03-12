# Analyse Fonctionnelle - LOT 8 : Système d'Évaluation & Notation

Ce document définit les exigences pour le système de réputation, incluant les notes, les avis détaillés et le calcul des badges de fiabilité.

## 1. Vue d'ensemble du Lot 8
L'objectif est de permettre aux utilisateurs (expéditeurs et transporteurs) de s'évaluer mutuellement après une transaction terminée afin de garantir la qualité du service et de valoriser les meilleurs éléments de la communauté via des badges.

---

## 2. Liste des User Stories (US)

### A. Évaluation après livraison
*   **US-029 : Notation par étoiles (Expéditeur)**
    *   *Action :* Une fois le colis marqué comme `completed`, l'expéditeur doit obligatoirement noter le transporteur.
    *   *Détails :* Score de 1 à 5 étoiles. Critères évaluables : Ponctualité, Communication, Soin du colis.
*   **US-030 : Avis détaillé**
    *   *Action :* Possibilité de laisser un commentaire textuel (min 50 caractères, max 500).
    *   *Détails :* Le transporteur dispose d'un droit de réponse unique à l'avis.
*   **US-031 : Modification de l'avis**
    *   *Action :* L'avis peut être modifié par son auteur pendant une période de 30 jours.

### B. Fiabilité et Badges (Transpoteur)
*   **US-064 : Attribution des badges**
    *   *Badge 'Identité vérifiée' :* Obtenu après validation des documents (LOT 1).
    *   *Badge 'Expert' :* Obtenu après 20 transports réussis.
    *   *Badge 'Super transporteur' :* Nécessite une note moyenne > 4.8/5 ET plus de 50 transports.
*   **US-065 : Visualisation de la progression**
    *   *Action :* Une barre de progression sur le profil indique le chemin vers le prochain badge.

### C. Historique et Profil Public
*   **US-017 : Consultation des avis**
    *   *Action :* Tout utilisateur peut voir les avis laissés sur un transporteur avant de le contacter.
    *   *Détails :* Tri par date (plus récent au plus ancien), résumé statistique (% de notes positives).

### D. Modération (Administration)
*   **US-103 : Signalement d'avis**
    *   *Action :* Possibilité de signaler un avis comme inapproprié ou insultant.
    *   *Action Admin :* Le modérateur peut masquer un avis après enquête.

---

## 3. Règles Métier Importantes

1.  **Réciprocité :** Bien que le focus soit sur le transporteur, l'expéditeur peut également être noté pour son sérieux (ponctualité au RDV, état du colis à la remise).
2.  **Délai de publication :** Pour éviter les pressions, l'avis est publié immédiatement mais la note globale n'est mise à jour qu'après 7 jours ou après que le transporteur ait aussi noté l'expéditeur.
3.  **Calcul de la Note Moyenne :** La moyenne est recalculée en temps réel (ou via un job planifié) à chaque nouvel avis.
4.  **Recalcul des Badges :** Les conditions d'obtention des badges sont vérifiées à chaque clôture de transaction. Un badge peut être perdu si la note baisse.

---

## 4. Analyse Technique Préliminaire

### Backend (Laravel)
1.  **Table `reviews` :** Stocke la note, le commentaire, les critères et le lien vers le `match_id`.
2.  **Table `user_badges` :** Pivot entre `users` et `badges` avec date d'obtention.
3.  **Observers :** Pour déclencher le recalcul de la moyenne du profil utilisateur à chaque nouvel enregistrement.

### Front-end (Flutter)
1.  **Widget de Rating :** Composant graphique à 5 étoiles.
2.  **Profil riche :** Affichage des badges sous forme d'icônes stylisées avec info-bulles explicatives.
3.  **Timeline d'avis :** Liste fluide avec affichage de la photo de l'auteur.
