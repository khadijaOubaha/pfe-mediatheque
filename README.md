# Réseau de médiathèques — PFE Challenge (sujet 4)

Application de gestion d'un réseau de deux médiathèques : catalogue partagé, exemplaires répartis entre les sites,
prêts, réservations avec file d'attente, pénalités et circulation des exemplaires.

**Stack** : Symfony 7.4, PHP 8.2, Doctrine ORM et Migrations, MySQL 8, EasyAdmin 5, Twig, PHPUnit, Docker.

## Démarrer

```bash
docker compose up --build
```

Puis ouvrir http://localhost:8000. Les comptes de démonstration et le détail sont dans [INSTALL.md](INSTALL.md).

## Ce que fait l'application

- **Catalogue public** : recherche par titre, auteur, ISBN ou éditeur, filtre par type, disponibilité par site.
- **Adhérents** : formules d'abonnement (nombre de prêts, durée, prolongations, tarif de pénalité) et abonnements.
- **Prêts** : emprunt, retour, prolongation, perte, avec contrôle des règles métier ; écran de **comptoir** pour le bibliothécaire.
- **Réservations** : file d'attente par édition, mise de côté 5 jours, transfert d'un exemplaire depuis l'autre site.
- **Pénalités** : retard calculé selon la formule, forfaits pour perte et dégradation.
- **Espace adhérent** : prêts en cours, prolongation, réservations, pénalités, historique.
- **Statistiques** : taux de rotation, documents les plus empruntés, documents jamais empruntés.
- **Import ISBN** : création et complément de fiches depuis Open Library, à l'unité ou par lot.
- **Rôles** : adhérent, bibliothécaire, administrateur ; back-office EasyAdmin pour le bibliothécaire.

## Documentation

- [docs/MODELE.md](docs/MODELE.md) : modèle de données et diagramme.
- [docs/DECISIONS.md](docs/DECISIONS.md) : journal des décisions et réponses aux questions à trancher.
- [INSTALL.md](INSTALL.md) : installation, comptes de démonstration, lancement et tests.

## Organisation du code

| Dossier | Contenu |
|---|---|
| `src/Entity`, `src/Enum` | Modèle de données |
| `src/Service` | Règles métier (`PretService`, `ReservationService`, `Regles`), statistiques, import ISBN |
| `src/Repository` | Requêtes Doctrine |
| `src/Controller` | Pages publiques, espace adhérent, comptoir, statistiques, import |
| `src/Controller/Admin` | Back-office EasyAdmin |
| `src/DataFixtures` | Données de démonstration |
| `templates/` | Vues Twig |
| `tests/` | Tests d'intégration et tests unitaires (40 tests) |
