# Installation et lancement

## Prérequis

- Docker et Docker Compose (Docker Desktop sous Windows ou macOS).
- Les ports **8000** (application), **8081** (Adminer) et **3307** (MySQL) libres sur la machine.

## Lancer l'application

```bash
git clone <url-du-depot>
cd pfe-mediatheque
docker compose up --build
```

La première construction télécharge les images et installe les dépendances : elle prend quelques minutes.
Au démarrage, le conteneur `app` :

1. attend que MySQL soit prêt ;
2. applique les migrations ;
3. charge les données de démonstration si elles ne sont pas déjà présentes (2 sites, 3 formules, une centaine d'éditions, 60 adhérents, prêts, réservations, pénalités, comptes de connexion).

Si le port 8000 est déjà utilisé, lancer par exemple `APP_PORT=8080 docker compose up --build`
(sous Windows CMD : `set APP_PORT=8080` puis `docker compose up --build`).

## Accès

| Service | Adresse |
|---|---|
| Catalogue public | http://localhost:8000/catalogue |
| Connexion | http://localhost:8000/connexion |
| Back-office (EasyAdmin) | http://localhost:8000/admin |
| Adminer (inspection de la base) | http://localhost:8081 (serveur `database`, utilisateur `app`, mot de passe `app`, base `mediatheque`) |

## Comptes de démonstration

Mots de passe de développement, uniquement pour la démonstration.

| Rôle | Identifiant | Mot de passe | Accès |
|---|---|---|---|
| Administrateur | `admin@mediatheque.test` | `admin123` | tout |
| Bibliothécaire | `biblio@mediatheque.test` | `biblio123` | comptoir (prêts et retours), back-office, statistiques, import ISBN |
| Adhérent | `ahmed.benali1@example.com` | `adherent123` | espace personnel : prêts, prolongations, réservations, pénalités |

Les deux adhérents suivants (`fatima.chraibi2@example.com`, `youssef.benjelloun3@example.com`) ont aussi un compte, avec le même mot de passe.

## Parcours à essayer

1. **Adhérent** : se connecter, ouvrir une œuvre du catalogue, réserver une édition, prolonger un prêt depuis « Mon espace ».
2. **Bibliothécaire** : ouvrir le **Comptoir**, enregistrer un prêt (email d'un adhérent + code-barres `MED-00001`), puis le retour ; observer les règles (limite de prêts, retard, file d'attente, pénalité).
3. **Statistiques** : taux de rotation, documents les plus empruntés, documents jamais empruntés.
4. **Import ISBN** : saisir un ou plusieurs ISBN pour créer des fiches depuis Open Library (ou, en ligne de commande, `docker compose exec app php bin/console app:import-isbn 9780743273565`).

## Lancer les tests

Les tests utilisent une base séparée, `mediatheque_test`, créée automatiquement au premier démarrage de MySQL.

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec app vendor/bin/phpunit
```

Résultat attendu : tous les tests passent (règles de prêt, retour, prolongation, réservation, transfert, pénalités, import ISBN).

## Arrêter et réinitialiser

```bash
docker compose down          # arrête les conteneurs, conserve les données
docker compose down -v       # supprime aussi la base : repart de zéro
```

## Remarques

- L'application tourne en mode **développement** avec le serveur intégré de PHP : adapté à une démonstration, pas à une mise en production.
- Les mots de passe présents dans `compose.yaml` et dans cette page sont des valeurs de développement.
- L'import ISBN interroge l'API publique d'Open Library : il nécessite un accès à Internet depuis le conteneur.
- Hors Docker, copier `.env.example` en `.env.local` et lancer MySQL seul avec `docker compose up -d database`.
