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

---

## 2026-11-13 — Environnement et tests

- **PHPUnit** est lancé via le fichier `phpunit.phar` sur le poste de développement : l'installation par Composer échouait sous Windows (extraction de fichiers verrouillée par l'antivirus). Dans Docker, PHPUnit s'installe normalement par Composer.
- Les tests de règles métier sont des **tests d'intégration** sur une base MySQL dédiée (`mediatheque_test`) : chaque test s'exécute dans une transaction annulée à la fin, donc la base reste propre.
- Les dates de référence des tests sont **fixes** pour que les résultats soient reproductibles.
- Une exception métier (`RegleMetierException`) est levée à chaque règle violée ; les tests vérifient le refus et, pour les cas réussis, l'état des entités.

## 2026-11-14 — Docker

- Image PHP 8.2 en ligne de commande avec le serveur intégré de PHP, en mode développement : suffisant pour la démonstration demandée, documenté comme tel dans `INSTALL.md`.
- Le Dockerfile conserve un **cache Composer** et **retente** l'installation des dépendances : le réseau utilisé pendant le développement était instable.
- Au démarrage, le conteneur applique les migrations puis charge les données de démonstration **seulement si les comptes de démonstration sont absents**, pour ne pas écraser des données au redémarrage.
- Une base `mediatheque_test` est créée automatiquement au premier démarrage de MySQL.

## 2026-11-14 — Comptes, rôles et sécurité

- Trois rôles : `ROLE_ADHERENT`, `ROLE_BIBLIOTHECAIRE` et `ROLE_ADMIN` (qui hérite du précédent).
- **User** est distinct d'**Adherent** : un compte peut exister sans fiche adhérent (bibliothécaire, administrateur), et seule une fiche liée donne accès à l'espace adhérent.
- `/admin`, `/comptoir`, `/statistiques` et `/import-isbn` sont réservés au bibliothécaire ; `/mon-espace` aux adhérents.
- Mots de passe hachés, formulaires protégés par jeton CSRF ; un adhérent ne peut agir que sur **ses** prêts et **ses** réservations.
- Les comptes de démonstration sont créés par les fixtures et rappelés sur la page de connexion (valeurs de développement).

## 2026-11-14 — Statistiques

- **Taux de rotation** = nombre de prêts des 12 derniers mois ÷ nombre d'exemplaires en circulation (hors perdus et retirés), pour le réseau et par site (site d'emprunt pour les prêts, site actuel pour les exemplaires).
- « Jamais empruntés » est calculé au niveau de l'**œuvre** : une œuvre n'est jamais empruntée si aucun exemplaire d'aucune de ses éditions n'a été prêté.

## 2026-11-14 — Import Open Library

Mise en œuvre de la réponse à la question 5 :

- L'ISBN saisi est **validé** (clé de contrôle) et ramené en ISBN-13 ; un ISBN-10 est converti.
- Une fiche inconnue est créée ; si une œuvre de même titre existe déjà, la nouvelle édition lui est **rattachée** (décision n°1).
- Une fiche existante n'est **jamais écrasée** : seuls ses champs vides sont complétés, et les écarts (éditeur, année, pages, titre) sont signalés.
- Un ISBN invalide, inconnu ou une API injoignable **n'interrompt pas un lot** : la ligne est marquée en échec.
- La langue d'une fiche créée est `fr` par défaut, car l'API ne la fournit pas ; le nom d'un auteur est découpé en prénom et nom en gardant les particules (« de », « van »…) dans le nom.
- L'appel réseau est isolé derrière une interface, ce qui permet de tester l'import sans accès à Internet. Un lot est limité à 50 ISBN et chaque appel est suivi d'une courte pause, par courtoisie envers l'API publique.

## Limites connues

- Les **écrans d'écriture du back-office EasyAdmin** (créer ou modifier un prêt, une réservation) écrivent directement dans la base et **ne passent pas** par les services métier : seuls le comptoir et l'espace adhérent appliquent toutes les règles. Le back-office sert à la consultation et à la correction, pas à la circulation des documents.
- Il n'y a pas d'écran de **création de comptes** : les comptes de démonstration sont créés par les fixtures.
- L'expiration des réservations échues est déclenchée **à la main** depuis le comptoir (ou par `ReservationService::expirerEchues`) ; il n'y a pas de tâche planifiée.
- Pas de paiement des pénalités dans l'interface : le champ « payée » se modifie depuis le back-office.
