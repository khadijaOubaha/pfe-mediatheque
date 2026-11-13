# Journal des décisions — Réseau de médiathèques

Ce document recense les règles métier retenues et les réponses aux cinq « Questions à trancher ».
Principe suivi : **trancher, noter l'hypothèse, avancer**. Chaque décision indique la règle, la raison, et ce qu'elle implique dans le code ou le modèle (`docs/MODELE.md`).

## 1. Règles de circulation

| # | Règle | Décision | Pourquoi / hypothèse |
|---|---|---|---|
| R1 | **Emprunter** | Il faut un abonnement actif à la date du jour (`dateDebut ≤ aujourd'hui ≤ dateFin`), un nombre de prêts en cours strictement inférieur à `Formule.maxPrets`, et aucun prêt en retard. | Les règles vivent dans `Formule` (donnée modifiable en back-office), pas dans le code. |
| R2 | **Durée du prêt** | `Formule.dureePretJours` (14, 21 ou 28 jours). `dateEcheance = dateEmprunt + durée`. | Une seule source de vérité : la formule de l'abonnement actif. |
| R3 | **Retour en retard** | Pénalité de motif `RETARD` = jours de retard × `Formule.penaliteParJour`. Jours de retard = écart en jours entre la date de retour et `dateEcheance`, jamais négatif. | Le jour d'échéance lui-même n'est pas en retard. |
| R4 | **Réservation** | Si un exemplaire de l'édition est déjà `DISPONIBLE` sur le site de retrait demandé, la réservation est refusée : l'adhérent l'emprunte directement. | Une réservation sert à attendre, pas à doubler un emprunt possible. |
| R5 | **File d'attente** | Par édition, parmi les réservations `EN_ATTENTE`, la plus ancienne `dateDemande` passe en premier. Pas de champ « position » stocké. | Position calculée, donc jamais incohérente (choix n°4 de `MODELE.md`). |
| R6 | **Mis de côté** | Au retour d'un exemplaire qui a une file d'attente, il passe `MIS_DE_COTE` pour le premier réservataire (réservation `A_RETIRER`, `dateLimiteRetrait = +5 jours`). Passé ce délai : réservation `EXPIREE`, exemplaire proposé au suivant, ou `DISPONIBLE` si la file est vide. | 5 jours est une constante nommée dans le code, facile à changer. |
| R7 | **Prolongation** | Autorisée seulement si : le prêt est en cours, pas en retard, `nbProlongations < Formule.maxProlongations`, et aucune réservation `EN_ATTENTE` sur l'édition. Nouvelle échéance = ancienne + `dureePretJours`. | Ne pas priver quelqu'un qui attend. |
| R8 | **Perdu / abîmé** | Pénalité forfaitaire : **50 DH** si perdu (exemplaire `PERDU`), **20 DH** si abîmé (état `ABIME`). Constantes dans le code, pas dans la base. | Montants fixes décidés par la médiathèque, non liés à la formule. |
| R9 | **Transactions** | Chaque opération (emprunter, retourner, prolonger, réserver, honorer) s'exécute dans une transaction, avec verrou pessimiste (`PESSIMISTIC_WRITE`) sur l'exemplaire. | Évite que deux bibliothécaires prêtent le même exemplaire en même temps. |

### Hypothèses assumées
- Les pénalités impayées **ne bloquent pas** un nouvel emprunt (seul un prêt en retard bloque). À revoir si l'entretien le demande.
- Un exemplaire abîmé reste empruntable (état `ABIME`) tant qu'un bibliothécaire ne le passe pas en `RETIRE`.
- Un adhérent ne peut pas avoir deux réservations actives sur la même édition.

## 2. Réponses aux cinq questions à trancher

**Q1 — Œuvre : quand deux éditions sont-elles la même œuvre ? Un film est-il la même œuvre que le roman ?**
Deux éditions (autre éditeur, autre année, autre langue, traduction) sont la **même œuvre**. Un film adapté d'un roman est une **œuvre distincte** : le support et les contributeurs diffèrent, et on ne veut pas qu'une réservation du livre soit honorée avec le DVD. Le champ `type` (`LIVRE`, `BD`, `DVD`) sert à les séparer.

**Q2 — Peut-on réserver un exemplaire qui se trouve sur un autre site ?**
Oui, par **transfert**. Si aucun exemplaire n'est disponible sur le site de retrait mais qu'un l'est ailleurs, il est envoyé (`Transfert` créé, exemplaire `EN_TRANSIT`). À sa réception il passe `MIS_DE_COTE` sur le site de retrait et le délai de 5 jours commence à ce moment-là.

**Q3 — Que se passe-t-il au bout de 5 jours si l'adhérent ne vient pas ?**
La réservation passe `EXPIREE`, l'exemplaire est attribué au réservataire suivant (nouveau délai de 5 jours) ou redevient `DISPONIBLE` s'il n'y a personne. L'expiration est traitée par une commande ou un appel de service qui peut être lancé régulièrement, et non par un champ calculé à la volée.

**Q4 — Contribution : comment représenter auteur, traducteur, illustrateur ?**
Par l'entité `Contribution` (édition + personne + rôle `AUTEUR`, `TRADUCTEUR`, `ILLUSTRATEUR`, `REALISATEUR`). Elle est rattachée à l'**édition** et non à l'œuvre, car le traducteur et l'illustrateur changent d'une édition à l'autre, alors que l'auteur reste le même.

**Q5 — Open Library : qui fait foi, notre catalogue ou la source externe ?**
**Notre catalogue prévaut toujours.** Open Library sert uniquement à compléter les champs vides (couverture, nombre de pages, description) et n'écrase jamais une donnée saisie. Si le service est indisponible, l'application fonctionne normalement.

## 3. Points d'attention pour l'entretien
- Toutes les valeurs configurables (max de prêts, durée, tarif par jour, prolongations) sont dans `Formule`. Les seules constantes en code sont les 5 jours de mise de côté et les forfaits perdu (50 DH) et abîmé (20 DH).
- Le statut d'un prêt n'est pas stocké : en cours = `dateRetour` nulle, en retard = échéance dépassée.
- Les règles R1 à R9 seront couvertes par des tests : au moins un cas qui passe et un cas refusé pour chacune.
