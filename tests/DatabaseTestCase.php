<?php

namespace App\Tests;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Formule;
use App\Entity\Oeuvre;
use App\Entity\Site;
use App\Enum\TypeOeuvre;
use App\Repository\AbonnementRepository;
use App\Repository\ExemplaireRepository;
use App\Repository\PretRepository;
use App\Repository\ReservationRepository;
use App\Service\PretService;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base des tests d'intégration : chaque test s'exécute dans une transaction
 * annulée à la fin, la base de test reste donc propre.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;
    protected PretService $prets;
    protected ReservationService $reservations;
    protected \DateTimeImmutable $maintenant;
    private int $compteur = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->beginTransaction();

        /** @var AbonnementRepository $abonnements */
        $abonnements = $this->em->getRepository(Abonnement::class);
        /** @var PretRepository $prets */
        $prets = $this->em->getRepository(\App\Entity\Pret::class);
        /** @var ReservationRepository $reservations */
        $reservations = $this->em->getRepository(\App\Entity\Reservation::class);
        /** @var ExemplaireRepository $exemplaires */
        $exemplaires = $this->em->getRepository(Exemplaire::class);

        $this->reservations = new ReservationService($this->em, $abonnements, $prets, $reservations, $exemplaires);
        $this->prets = new PretService($this->em, $abonnements, $prets, $reservations, $this->reservations);

        // Date de référence fixe pour des tests reproductibles.
        $this->maintenant = new \DateTimeImmutable('2026-11-13 10:00:00');
    }

    protected function tearDown(): void
    {
        $connexion = $this->em->getConnection();
        if ($connexion->isTransactionActive()) {
            $connexion->rollBack();
        }
        parent::tearDown();
    }

    // ---------- Fabriques de données de test ----------

    protected function site(string $nom = 'Centre'): Site
    {
        $site = (new Site())->setNom($nom);
        $this->em->persist($site);
        $this->em->flush();

        return $site;
    }

    protected function formule(int $maxPrets = 3, int $duree = 14, int $maxProlongations = 1, string $penaliteParJour = '1.00'): Formule
    {
        $formule = (new Formule())
            ->setNom('Formule test '.++$this->compteur)
            ->setPrix('100.00')
            ->setMaxPrets($maxPrets)
            ->setDureePretJours($duree)
            ->setMaxProlongations($maxProlongations)
            ->setPenaliteParJour($penaliteParJour);
        $this->em->persist($formule);
        $this->em->flush();

        return $formule;
    }

    /** Crée un adhérent ; s'il reçoit une formule, il a un abonnement valide d'un an commencé 60 jours avant la date de référence. */
    protected function adherent(?Formule $formule = null): Adherent
    {
        $adherent = (new Adherent())
            ->setNom('Nom'.++$this->compteur)
            ->setPrenom('Prenom')
            ->setEmail(sprintf('adherent%d@example.com', $this->compteur))
            ->setDateInscription($this->maintenant->modify('-90 days'));
        $this->em->persist($adherent);

        if (null !== $formule) {
            $debut = $this->maintenant->modify('-60 days');
            $abonnement = (new Abonnement())
                ->setAdherent($adherent)
                ->setFormule($formule)
                ->setDateDebut($debut)
                ->setDateFin($debut->modify('+1 year'));
            $this->em->persist($abonnement);
        }
        $this->em->flush();

        return $adherent;
    }

    protected function edition(): Edition
    {
        $oeuvre = (new Oeuvre())->setTitre('Oeuvre '.++$this->compteur)->setType(TypeOeuvre::LIVRE);
        $this->em->persist($oeuvre);

        $edition = (new Edition())->setOeuvre($oeuvre)->setLangue('fr');
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }

    protected function exemplaire(Edition $edition, Site $site): Exemplaire
    {
        $exemplaire = (new Exemplaire())
            ->setEdition($edition)
            ->setCodeBarres(sprintf('TEST-%05d', ++$this->compteur))
            ->setSiteOrigine($site)
            ->setSiteCourant($site);
        $this->em->persist($exemplaire);
        $this->em->flush();

        return $exemplaire;
    }
}
