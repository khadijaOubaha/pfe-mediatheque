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
3. charge les données de démonstration si la base est vide (2 sites, 3 formules, une centaine d'éditions, 60 adhérents, prêts, réservations, pénalités).

## Accès

| Service | Adresse |
|---|---|
| Back-office (EasyAdmin) | http://localhost:8000/admin |
| Adminer (inspection de la base) | http://localhost:8081 (serveur `database`, utilisateur `app`, mot de passe `app`, base `mediatheque`) |

## Lancer les tests

Les tests utilisent une base séparée, `mediatheque_test`, créée automatiquement au premier démarrage de MySQL.

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec app vendor/bin/phpunit
```

Résultat attendu : tous les tests passent (règles de prêt, retour, prolongation, réservation, transfert, pénalités).

## Arrêter et réinitialiser

```bash
docker compose down          # arrête les conteneurs, conserve les données
docker compose down -v       # supprime aussi la base : repart de zéro
```

## Remarques

- L'application tourne en mode **développement** avec le serveur intégré de PHP : adapté à une démonstration, pas à une mise en production.
- Les mots de passe présents dans `compose.yaml` sont des valeurs de développement.
- Hors Docker, copier `.env.example` en `.env.local` et lancer MySQL seul avec `docker compose up -d database`.
