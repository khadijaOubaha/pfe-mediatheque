# Réseau de médiathèques — PFE Challenge (sujet 4)

Application de gestion d'un réseau de deux médiathèques : catalogue partagé, exemplaires répartis entre les sites,
prêts, réservations avec file d'attente, pénalités et circulation des exemplaires.

**Stack** : Symfony 7.4, PHP 8.2, Doctrine ORM et Migrations, MySQL 8, EasyAdmin 5, PHPUnit, Docker.

## Démarrer

```bash
docker compose up --build
```

Puis ouvrir http://localhost:8000/admin. Le détail est dans [INSTALL.md](INSTALL.md).

## Ce que fait l'application

- **Catalogue** : œuvres, éditions, exemplaires, personnes et leurs rôles (auteur, traducteur, illustrateur, réalisateur).
- **Adhérents** : formules d'abonnement (nombre de prêts, durée, prolongations, tarif de pénalité) et abonnements.
- **Prêts** : emprunt, retour, prolongation, perte, avec contrôle des règles métier.
- **Réservations** : file d'attente par édition, mise de côté 5 jours, transfert automatique d'un exemplaire depuis l'autre site.
- **Pénalités** : retard calculé selon la formule, forfaits pour perte et dégradation.
- **Back-office** : EasyAdmin sur toutes les entités.

## Documentation

- [docs/MODELE.md](docs/MODELE.md) : modèle de données et diagramme.
- [docs/DECISIONS.md](docs/DECISIONS.md) : journal des décisions et réponses aux questions à trancher.
- [INSTALL.md](INSTALL.md) : installation, lancement et tests.

## Organisation du code

| Dossier | Contenu |
|---|---|
| `src/Entity`, `src/Enum` | Modèle de données |
| `src/Service` | Règles métier (`PretService`, `ReservationService`, `Regles`) |
| `src/Repository` | Requêtes Doctrine |
| `src/Controller/Admin` | Back-office EasyAdmin |
| `src/DataFixtures` | Données de démonstration |
| `tests/` | Tests d'intégration des règles métier |
