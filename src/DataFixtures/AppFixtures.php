<?php

namespace App\DataFixtures;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Contribution;
use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Formule;
use App\Entity\Oeuvre;
use App\Entity\Penalite;
use App\Entity\Personne;
use App\Entity\Pret;
use App\Entity\Reservation;
use App\Entity\Site;
use App\Entity\Transfert;
use App\Enum\EtatExemplaire;
use App\Enum\MotifPenalite;
use App\Enum\RoleContribution;
use App\Enum\StatutExemplaire;
use App\Enum\StatutReservation;
use App\Enum\TypeOeuvre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Données de démonstration : 2 sites, 3 formules, ~70 oeuvres, ~270 exemplaires,
 * 60 adhérents, des prêts passés et en cours, des réservations, des pénalités, des transferts.
 * Les données sont générées de façon reproductible (graine fixe).
 */
class AppFixtures extends Fixture
{
    private \DateTimeImmutable $now;

    public function load(ObjectManager $manager): void
    {
        mt_srand(2026);
        $this->now = new \DateTimeImmutable('today');

        // ---------- Sites ----------
        $sites = [];
        foreach ([
            ['Médiathèque Centre-ville', 'Quartier Centre, Agadir'],
            ['Médiathèque Anza', 'Quartier Anza, Agadir'],
        ] as [$nom, $adresse]) {
            $site = (new Site())->setNom($nom)->setAdresse($adresse);
            $manager->persist($site);
            $sites[] = $site;
        }

        // ---------- Formules ----------
        $formules = [];
        foreach ([
            ['Basique', '100.00', 3, 14, 1, '1.00'],
            ['Standard', '180.00', 6, 21, 2, '0.75'],
            ['Premium', '300.00', 10, 28, 3, '0.50'],
        ] as [$nom, $prix, $max, $duree, $prolongations, $penalite]) {
            $formule = (new Formule())
                ->setNom($nom)
                ->setPrix($prix)
                ->setMaxPrets($max)
                ->setDureePretJours($duree)
                ->setMaxProlongations($prolongations)
                ->setPenaliteParJour($penalite);
            $manager->persist($formule);
            $formules[] = $formule;
        }

        // ---------- Catalogue ----------
        $catalogue = [
            // Livres
            ['Le Petit Prince', 'Antoine', 'de Saint-Exupéry', 'LIVRE'],
            ["L'Étranger", 'Albert', 'Camus', 'LIVRE'],
            ['La Peste', 'Albert', 'Camus', 'LIVRE'],
            ['Les Misérables', 'Victor', 'Hugo', 'LIVRE'],
            ['Notre-Dame de Paris', 'Victor', 'Hugo', 'LIVRE'],
            ['Germinal', 'Émile', 'Zola', 'LIVRE'],
            ['Thérèse Raquin', 'Émile', 'Zola', 'LIVRE'],
            ['Candide', '', 'Voltaire', 'LIVRE'],
            ['Le Rouge et le Noir', '', 'Stendhal', 'LIVRE'],
            ['Madame Bovary', 'Gustave', 'Flaubert', 'LIVRE'],
            ['Bel-Ami', 'Guy', 'de Maupassant', 'LIVRE'],
            ['Le Horla', 'Guy', 'de Maupassant', 'LIVRE'],
            ['Le Comte de Monte-Cristo', 'Alexandre', 'Dumas', 'LIVRE'],
            ['Vingt mille lieues sous les mers', 'Jules', 'Verne', 'LIVRE'],
            ['Le Tour du monde en quatre-vingts jours', 'Jules', 'Verne', 'LIVRE'],
            ['1984', 'George', 'Orwell', 'LIVRE'],
            ['Le Meilleur des mondes', 'Aldous', 'Huxley', 'LIVRE'],
            ['Fahrenheit 451', 'Ray', 'Bradbury', 'LIVRE'],
            ['Le Vieil Homme et la mer', 'Ernest', 'Hemingway', 'LIVRE'],
            ['Cent ans de solitude', 'Gabriel', 'García Márquez', 'LIVRE'],
            ["L'Alchimiste", 'Paulo', 'Coelho', 'LIVRE'],
            ["Harry Potter à l'école des sorciers", 'J. K.', 'Rowling', 'LIVRE'],
            ['Le Hobbit', 'J. R. R.', 'Tolkien', 'LIVRE'],
            ['Orgueil et Préjugés', 'Jane', 'Austen', 'LIVRE'],
            ["Le Journal d'Anne Frank", 'Anne', 'Frank', 'LIVRE'],
            ['Sapiens', 'Yuval Noah', 'Harari', 'LIVRE'],
            ['Le Petit Nicolas', 'René', 'Goscinny', 'LIVRE'],
            ['Matilda', 'Roald', 'Dahl', 'LIVRE'],
            ['Charlie et la chocolaterie', 'Roald', 'Dahl', 'LIVRE'],
            ["Alice au pays des merveilles", 'Lewis', 'Carroll', 'LIVRE'],
            ['Robinson Crusoé', 'Daniel', 'Defoe', 'LIVRE'],
            ['Moby Dick', 'Herman', 'Melville', 'LIVRE'],
            ['Don Quichotte', 'Miguel', 'de Cervantes', 'LIVRE'],
            ['Le Pain nu', 'Mohamed', 'Choukri', 'LIVRE'],
            ["L'Enfant de sable", 'Tahar', 'Ben Jelloun', 'LIVRE'],
            ['Chanson douce', 'Leïla', 'Slimani', 'LIVRE'],
            ['Le Passé simple', 'Driss', 'Chraïbi', 'LIVRE'],
            ['La Boîte à merveilles', 'Ahmed', 'Sefrioui', 'LIVRE'],
            ["Mémoires d'Hadrien", 'Marguerite', 'Yourcenar', 'LIVRE'],
            // Bandes dessinées
            ['Tintin au Tibet', '', 'Hergé', 'BD'],
            ['Le Trésor de Rackham le Rouge', '', 'Hergé', 'BD'],
            ['Astérix le Gaulois', 'René', 'Goscinny', 'BD', ['Albert', 'Uderzo']],
            ['Lucky Luke : La Ballade des Dalton', '', 'Morris', 'BD'],
            ['Spirou et Fantasio : Le Nid des Marsupilamis', 'André', 'Franquin', 'BD'],
            ['Gaston Lagaffe : Gaffes et gadgets', 'André', 'Franquin', 'BD'],
            ["Blake et Mortimer : Le Secret de l'Espadon", 'Edgar P.', 'Jacobs', 'BD'],
            ['Persepolis', 'Marjane', 'Satrapi', 'BD'],
            ['Les Schtroumpfs noirs', '', 'Peyo', 'BD'],
            ["L'Arabe du futur", 'Riad', 'Sattouf', 'BD'],
            ['Maus', 'Art', 'Spiegelman', 'BD'],
            ['Largo Winch : L\'Héritier', 'Jean', 'Van Hamme', 'BD'],
            ['Blacksad', 'Juan', 'Díaz Canales', 'BD', ['Juanjo', 'Guarnido']],
            ['Achille Talon et le Trône de Fer', '', 'Greg', 'BD'],
            ['Les Cités obscures : La Fièvre d\'Urbicande', 'François', 'Schuiten', 'BD'],
            // DVD
            ['Le Fabuleux Destin d\'Amélie Poulain', 'Jean-Pierre', 'Jeunet', 'DVD'],
            ['Intouchables', 'Olivier', 'Nakache', 'DVD'],
            ['La Haine', 'Mathieu', 'Kassovitz', 'DVD'],
            ['Casablanca', 'Michael', 'Curtiz', 'DVD'],
            ['Le Parrain', 'Francis Ford', 'Coppola', 'DVD'],
            ['Titanic', 'James', 'Cameron', 'DVD'],
            ['Inception', 'Christopher', 'Nolan', 'DVD'],
            ['Le Roi Lion', 'Roger', 'Allers', 'DVD'],
            ['Les Évadés', 'Frank', 'Darabont', 'DVD'],
            ['Forrest Gump', 'Robert', 'Zemeckis', 'DVD'],
            ['Parasite', 'Joon-ho', 'Bong', 'DVD'],
            ['Le Voyage de Chihiro', 'Hayao', 'Miyazaki', 'DVD'],
            ['Gladiator', 'Ridley', 'Scott', 'DVD'],
            ['Matrix', 'Lana', 'Wachowski', 'DVD'],
            ['Pulp Fiction', 'Quentin', 'Tarantino', 'DVD'],
            ['Casanegra', 'Nour-Eddine', 'Lakhmari', 'DVD'],
            ['Much Loved', 'Nabil', 'Ayouch', 'DVD'],
        ];

        $editeurs = [
            'LIVRE' => ['Gallimard', 'Flammarion', 'Hachette', 'Folio', 'Le Livre de Poche', 'Albin Michel', 'Éditions Eddif', 'Le Fennec'],
            'BD' => ['Dupuis', 'Casterman', 'Dargaud', 'Glénat', 'Delcourt'],
            'DVD' => ['Gaumont', 'Pathé', 'Warner Home Video', 'Studio Canal'],
        ];

        $personnes = [];
        $personne = function (string $prenom, string $nom) use (&$personnes, $manager): Personne {
            $cle = $prenom.'|'.$nom;
            if (!isset($personnes[$cle])) {
                $p = (new Personne())->setNom($nom)->setPrenom('' === $prenom ? null : $prenom);
                $manager->persist($p);
                $personnes[$cle] = $p;
            }

            return $personnes[$cle];
        };

        $traducteurs = [$personne('Hélène', 'Marchand'), $personne('Karim', 'Bensouda'), $personne('Claire', 'Dumas-Roux')];

        $exemplaires = [];
        $rares = [];     // exemplaires uniques de leur édition (pour les réservations)
        $compteurEdition = 0;
        $compteurExemplaire = 0;
        $compteurIsbn = 0;

        foreach ($catalogue as $i => $item) {
            [$titre, $prenom, $nom, $type] = $item;
            $illustrateur = $item[4] ?? null;

            $oeuvre = (new Oeuvre())->setTitre($titre)->setType(TypeOeuvre::from($type));
            $manager->persist($oeuvre);

            $nbEditions = 0 === mt_rand(0, 3) ? 2 : 1;
            for ($e = 0; $e < $nbEditions; ++$e) {
                ++$compteurEdition;
                $edition = (new Edition())
                    ->setOeuvre($oeuvre)
                    ->setIsbn($this->isbn(++$compteurIsbn))
                    ->setEditeur($editeurs[$type][mt_rand(0, count($editeurs[$type]) - 1)])
                    ->setAnneePublication(mt_rand(1985, 2024))
                    ->setLangue('fr')
                    ->setNbPages('DVD' === $type ? null : mt_rand(90, 620));
                $manager->persist($edition);

                $role = 'DVD' === $type ? RoleContribution::REALISATEUR : RoleContribution::AUTEUR;
                $this->contribuer($manager, $edition, $personne($prenom, $nom), $role);
                if ($illustrateur) {
                    $this->contribuer($manager, $edition, $personne($illustrateur[0], $illustrateur[1]), RoleContribution::ILLUSTRATEUR);
                }
                if ('LIVRE' === $type && 0 === $i % 6) {
                    $this->contribuer($manager, $edition, $traducteurs[$i % 3], RoleContribution::TRADUCTEUR);
                }

                $nbExemplaires = 0 === $compteurEdition % 7 ? 1 : mt_rand(2, 4);
                for ($k = 0; $k < $nbExemplaires; ++$k) {
                    $site = $sites[mt_rand(0, 1)];
                    $exemplaire = (new Exemplaire())
                        ->setEdition($edition)
                        ->setCodeBarres(sprintf('MED-%05d', ++$compteurExemplaire))
                        ->setSiteOrigine($site)
                        ->setSiteCourant($site);
                    $manager->persist($exemplaire);
                    $exemplaires[] = $exemplaire;
                    if (1 === $nbExemplaires) {
                        $rares[] = $exemplaire;
                    }
                }
            }
        }

        // ---------- Adhérents et abonnements ----------
        $prenoms = ['Ahmed', 'Fatima', 'Youssef', 'Khadija', 'Omar', 'Salma', 'Mehdi', 'Imane', 'Hamza', 'Nadia',
            'Amine', 'Sara', 'Karim', 'Zineb', 'Anas', 'Houda', 'Rachid', 'Meryem', 'Yassine', 'Hajar',
            'Soufiane', 'Laila', 'Walid', 'Asmae', 'Bilal', 'Kenza', 'Ayoub', 'Sanae', 'Reda', 'Ghita'];
        $noms = ['Benali', 'Idrissi', 'Alaoui', 'Berrada', 'Tazi', 'Bennani', 'Lahlou', 'Chraibi', 'Fassi', 'Amrani',
            'Mansouri', 'Ouazzani', 'Zniber', 'Skalli', 'Benjelloun', 'Sebti', 'Naciri', 'Haddad', 'Bouzidi', 'Kettani'];

        $adherents = [];
        for ($i = 0; $i < 60; ++$i) {
            $formule = $formules[$i % 3];
            $debut = $this->now->modify(sprintf('-%d days', mt_rand(30, 300)));

            $adherent = (new Adherent())
                ->setPrenom($prenoms[$i % 30])
                ->setNom($noms[($i * 7) % 20])
                ->setEmail(strtolower(sprintf('%s.%s%d@example.com', $prenoms[$i % 30], $noms[($i * 7) % 20], $i + 1)))
                ->setTelephone(sprintf('06%08d', mt_rand(10000000, 99999999)))
                ->setDateInscription(0 === $i % 6 ? $this->now->modify('-700 days') : $debut);
            $manager->persist($adherent);

            $abonnement = (new Abonnement())
                ->setAdherent($adherent)
                ->setFormule($formule)
                ->setDateDebut($debut)
                ->setDateFin($debut->modify('+1 year'));
            $manager->persist($abonnement);

            if (0 === $i % 6) {
                // ancien abonnement expiré (historique)
                $ancien = (new Abonnement())
                    ->setAdherent($adherent)
                    ->setFormule($formules[0])
                    ->setDateDebut($this->now->modify('-700 days'))
                    ->setDateFin($this->now->modify('-335 days'));
                $manager->persist($ancien);
            }

            $adherents[] = ['entity' => $adherent, 'formule' => $formule, 'charge' => 0];
        }

        // ---------- Prêts passés (rendus) et pénalités de retard ----------
        for ($n = 0; $n < 160; ++$n) {
            $a = mt_rand(0, count($adherents) - 1);
            $exemplaire = $exemplaires[mt_rand(0, count($exemplaires) - 1)];
            /** @var Formule $formule */
            $formule = $adherents[$a]['formule'];
            $duree = $formule->getDureePretJours();

            $emprunt = $this->daysAgo(mt_rand(90, 380));
            $echeance = $emprunt->modify(sprintf('+%d days', $duree))->setTime(0, 0);
            $retour = $echeance->modify(sprintf('%+d days', mt_rand(-6, 10)))->setTime(mt_rand(9, 18), mt_rand(0, 59));
            if ($retour >= $this->now) {
                $retour = $this->now->modify('-1 day');
            }

            $pret = (new Pret())
                ->setExemplaire($exemplaire)
                ->setAdherent($adherents[$a]['entity'])
                ->setSiteEmprunt($exemplaire->getSiteCourant())
                ->setDateEmprunt($emprunt)
                ->setDateEcheance($echeance)
                ->setDateRetour($retour)
                ->setNbProlongations(0 === mt_rand(0, 4) ? 1 : 0);
            $manager->persist($pret);

            $retourJour = $retour->setTime(0, 0);
            if ($retourJour > $echeance) {
                $joursRetard = (int) $echeance->diff($retourJour)->days;
                $penalite = (new Penalite())
                    ->setAdherent($adherents[$a]['entity'])
                    ->setPret($pret)
                    ->setMotif(MotifPenalite::RETARD)
                    ->setMontant(number_format($joursRetard * (float) $formule->getPenaliteParJour(), 2, '.', ''))
                    ->setDate($retour)
                    ->setPayee(mt_rand(1, 10) <= 7);
                $manager->persist($penalite);
            }
        }

        // ---------- Prêts en cours ----------
        $libres = $exemplaires;
        shuffle($libres);
        $utilises = [];
        $pretsRares = [];

        $creerPretEnCours = function (Exemplaire $exemplaire) use (&$adherents, $manager): ?Pret {
            for ($essai = 0; $essai < 30; ++$essai) {
                $a = mt_rand(0, count($adherents) - 1);
                /** @var Formule $formule */
                $formule = $adherents[$a]['formule'];
                if ($adherents[$a]['charge'] >= $formule->getMaxPrets()) {
                    continue;
                }
                $duree = $formule->getDureePretJours();
                $emprunt = $this->daysAgo(mt_rand(1, $duree + 10));
                $pret = (new Pret())
                    ->setExemplaire($exemplaire)
                    ->setAdherent($adherents[$a]['entity'])
                    ->setSiteEmprunt($exemplaire->getSiteCourant())
                    ->setDateEmprunt($emprunt)
                    ->setDateEcheance($emprunt->modify(sprintf('+%d days', $duree))->setTime(0, 0));
                $exemplaire->setStatut(StatutExemplaire::EMPRUNTE);
                $manager->persist($pret);
                ++$adherents[$a]['charge'];

                return $pret;
            }

            return null;
        };

        foreach (array_slice($rares, 0, 8) as $exemplaire) {
            $utilises[spl_object_id($exemplaire)] = true;
            if ($pret = $creerPretEnCours($exemplaire)) {
                $pretsRares[] = $pret;
            }
        }

        $prendre = function () use (&$libres, &$utilises): ?Exemplaire {
            while ($libres) {
                $exemplaire = array_pop($libres);
                if (!isset($utilises[spl_object_id($exemplaire)])) {
                    $utilises[spl_object_id($exemplaire)] = true;

                    return $exemplaire;
                }
            }

            return null;
        };

        for ($n = 0; $n < 32; ++$n) {
            if (($exemplaire = $prendre()) && $exemplaire instanceof Exemplaire) {
                $creerPretEnCours($exemplaire);
            }
        }

        // ---------- Réservations ----------
        foreach ($pretsRares as $pret) {
            $nbFile = mt_rand(1, 2);
            for ($f = 0; $f < $nbFile; ++$f) {
                for ($essai = 0; $essai < 20; ++$essai) {
                    $a = mt_rand(0, count($adherents) - 1);
                    if ($adherents[$a]['entity'] !== $pret->getAdherent()) {
                        $reservation = (new Reservation())
                            ->setEdition($pret->getExemplaire()->getEdition())
                            ->setAdherent($adherents[$a]['entity'])
                            ->setSiteRetrait($sites[mt_rand(0, 1)])
                            ->setDateDemande($this->daysAgo(mt_rand(0, 10)))
                            ->setStatut(StatutReservation::EN_ATTENTE);
                        $manager->persist($reservation);
                        break;
                    }
                }
            }
        }

        // 2 réservations dont l'exemplaire est mis de côté, à retirer
        for ($n = 0; $n < 2; ++$n) {
            $exemplaire = $prendre();
            if (!$exemplaire) {
                break;
            }
            $exemplaire->setStatut(StatutExemplaire::MIS_DE_COTE);
            $reservation = (new Reservation())
                ->setEdition($exemplaire->getEdition())
                ->setAdherent($adherents[mt_rand(0, count($adherents) - 1)]['entity'])
                ->setSiteRetrait($exemplaire->getSiteCourant())
                ->setDateDemande($this->daysAgo(mt_rand(3, 8)))
                ->setStatut(StatutReservation::A_RETIRER)
                ->setExemplaireReserve($exemplaire)
                ->setDateLimiteRetrait($this->now->modify(sprintf('+%d days', mt_rand(2, 6))));
            $manager->persist($reservation);
        }

        // historique de réservations terminées
        foreach ([StatutReservation::HONOREE, StatutReservation::ANNULEE, StatutReservation::EXPIREE] as $statut) {
            $reservation = (new Reservation())
                ->setEdition($exemplaires[mt_rand(0, count($exemplaires) - 1)]->getEdition())
                ->setAdherent($adherents[mt_rand(0, count($adherents) - 1)]['entity'])
                ->setSiteRetrait($sites[mt_rand(0, 1)])
                ->setDateDemande($this->daysAgo(mt_rand(60, 200)))
                ->setStatut($statut);
            $manager->persist($reservation);
        }

        // ---------- Exemplaires perdus ou abîmés ----------
        for ($n = 0; $n < 3; ++$n) {
            if ($exemplaire = $prendre()) {
                $exemplaire->setStatut(StatutExemplaire::PERDU);
            }
        }
        for ($n = 0; $n < 2; ++$n) {
            if ($exemplaire = $prendre()) {
                $exemplaire->setEtat(EtatExemplaire::ABIME)->setStatut(StatutExemplaire::RETIRE);
            }
        }

        // ---------- Transferts entre sites ----------
        for ($n = 0; $n < 8; ++$n) {
            $exemplaire = $prendre();
            if (!$exemplaire) {
                break;
            }
            $origine = $exemplaire->getSiteOrigine();
            $destination = $origine === $sites[0] ? $sites[1] : $sites[0];
            $enTransit = $n >= 6;

            $transfert = (new Transfert())
                ->setExemplaire($exemplaire)
                ->setSiteOrigine($origine)
                ->setSiteDestination($destination)
                ->setDateEnvoi($enTransit ? $this->daysAgo(mt_rand(1, 2)) : $this->daysAgo(mt_rand(20, 200)));
            if ($enTransit) {
                $exemplaire->setStatut(StatutExemplaire::EN_TRANSIT);
            } else {
                $transfert->setDateReception($transfert->getDateEnvoi()->modify(sprintf('+%d days', mt_rand(1, 4))));
                $exemplaire->setSiteCourant($destination);
            }
            $manager->persist($transfert);
        }

        $manager->flush();
    }

    private function daysAgo(int $jours): \DateTimeImmutable
    {
        return $this->now->modify(sprintf('-%d days', $jours))->setTime(mt_rand(9, 18), mt_rand(0, 59));
    }

    private function contribuer(ObjectManager $manager, Edition $edition, Personne $personne, RoleContribution $role): void
    {
        $contribution = (new Contribution())->setPersonne($personne)->setRole($role);
        $edition->addContribution($contribution);
        $manager->persist($contribution);
    }

    /** ISBN-13 fictif mais valide (clé de contrôle calculée). */
    private function isbn(int $i): string
    {
        $base = '97829'.str_pad((string) (10000 + $i * 7), 7, '0', STR_PAD_LEFT);
        $somme = 0;
        foreach (str_split($base) as $position => $chiffre) {
            $somme += (int) $chiffre * (0 === $position % 2 ? 1 : 3);
        }

        return $base.((10 - $somme % 10) % 10);
    }
}
