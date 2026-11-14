<?php

namespace App\Tests\Service;

use App\Entity\Penalite;
use App\Enum\EtatExemplaire;
use App\Enum\MotifPenalite;
use App\Enum\StatutExemplaire;
use App\Enum\StatutReservation;
use App\Exception\RegleMetierException;
use App\Tests\DatabaseTestCase;

class PretServiceTest extends DatabaseTestCase
{
    // ---------- Emprunter ----------

    public function testEmpruntCreeUnPretAvecLaDureeDeLaFormule(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14));
        $exemplaire = $this->exemplaire($this->edition(), $site);

        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->assertSame('2026-11-27', $pret->getDateEcheance()->format('Y-m-d'));
        $this->assertNull($pret->getDateRetour());
        $this->assertSame(StatutExemplaire::EMPRUNTE, $exemplaire->getStatut());
    }

    public function testEmpruntRefuseSansAbonnement(): void
    {
        $adherent = $this->adherent(null);
        $exemplaire = $this->exemplaire($this->edition(), $this->site());

        $this->expectException(RegleMetierException::class);
        $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);
    }

    public function testEmpruntRefuseAuDelaDuNombreMaximalDePrets(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(1));
        $premier = $this->exemplaire($this->edition(), $site);
        $second = $this->exemplaire($this->edition(), $site);

        $this->prets->emprunter($adherent, $premier, $this->maintenant);

        $this->expectException(RegleMetierException::class);
        $this->prets->emprunter($adherent, $second, $this->maintenant);
    }

    public function testEmpruntRefuseSiExemplaireIndisponible(): void
    {
        $adherent = $this->adherent($this->formule());
        $exemplaire = $this->exemplaire($this->edition(), $this->site());
        $exemplaire->setStatut(StatutExemplaire::PERDU);
        $this->em->flush();

        $this->expectException(RegleMetierException::class);
        $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);
    }

    public function testEmpruntRefuseSiAdherentALunPretEnRetard(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14));
        $ancien = $this->exemplaire($this->edition(), $site);
        $nouveau = $this->exemplaire($this->edition(), $site);

        // Emprunté il y a 30 jours pour 14 jours : en retard depuis 16 jours.
        $this->prets->emprunter($adherent, $ancien, $this->maintenant->modify('-30 days'));

        $this->expectException(RegleMetierException::class);
        $this->prets->emprunter($adherent, $nouveau, $this->maintenant);
    }

    // ---------- Retourner ----------

    public function testRetourDansLesDelaisSansPenalite(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14));
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->prets->retourner($pret, $site, false, $this->maintenant->modify('+5 days'));

        $this->assertNotNull($pret->getDateRetour());
        $this->assertSame(StatutExemplaire::DISPONIBLE, $exemplaire->getStatut());
        $this->assertCount(0, $this->em->getRepository(Penalite::class)->findBy(['pret' => $pret]));
    }

    public function testRetourEnRetardCreeUnePenaliteSelonLeTarifDeLaFormule(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14, 1, '1.00'));
        $exemplaire = $this->exemplaire($this->edition(), $site);

        // Emprunté il y a 20 jours, échéance il y a 6 jours : 6 jours de retard.
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant->modify('-20 days'));
        $this->prets->retourner($pret, $site, false, $this->maintenant);

        $penalites = $this->em->getRepository(Penalite::class)->findBy(['pret' => $pret]);
        $this->assertCount(1, $penalites);
        $this->assertSame(MotifPenalite::RETARD, $penalites[0]->getMotif());
        $this->assertSame('6.00', $penalites[0]->getMontant());
    }

    public function testRetourSertLePremierReservantDeLaFile(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $premier = $this->adherent($formule);
        $second = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);

        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $reservationPremier = $this->reservations->reserver($premier, $edition, $site, $this->maintenant);
        $reservationSecond = $this->reservations->reserver($second, $edition, $site, $this->maintenant->modify('+1 hour'));

        $retour = $this->maintenant->modify('+3 days');
        $this->prets->retourner($pret, $site, false, $retour);

        $this->assertSame(StatutReservation::A_RETIRER, $reservationPremier->getStatut());
        $this->assertSame(StatutReservation::EN_ATTENTE, $reservationSecond->getStatut());
        $this->assertSame(StatutExemplaire::MIS_DE_COTE, $exemplaire->getStatut());
        $this->assertSame('2026-11-21', $reservationPremier->getDateLimiteRetrait()->format('Y-m-d'));
    }

    public function testRetourAbimeRetireLExemplaireEtCreeUneForfait(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule());
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->prets->retourner($pret, $site, true, $this->maintenant->modify('+2 days'));

        $this->assertSame(StatutExemplaire::RETIRE, $exemplaire->getStatut());
        $this->assertSame(EtatExemplaire::ABIME, $exemplaire->getEtat());
        $penalites = $this->em->getRepository(Penalite::class)->findBy(['pret' => $pret]);
        $this->assertCount(1, $penalites);
        $this->assertSame(MotifPenalite::ABIME, $penalites[0]->getMotif());
    }

    // ---------- Prolonger ----------

    public function testProlongationRepousseLEcheanceDeLaDureeDeLaFormule(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14, 1));
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->prets->prolonger($pret, $this->maintenant->modify('+3 days'));

        $this->assertSame('2026-12-11', $pret->getDateEcheance()->format('Y-m-d'));
        $this->assertSame(1, $pret->getNbProlongations());
    }

    public function testProlongationRefuseeAuDelaDuMaximum(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14, 1));
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);
        $this->prets->prolonger($pret, $this->maintenant->modify('+3 days'));

        $this->expectException(RegleMetierException::class);
        $this->prets->prolonger($pret, $this->maintenant->modify('+4 days'));
    }

    public function testProlongationRefuseeSiQuelquUnAttendLEdition(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $attendant = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);
        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $this->reservations->reserver($attendant, $edition, $site, $this->maintenant);

        $this->expectException(RegleMetierException::class);
        $this->prets->prolonger($pret, $this->maintenant->modify('+2 days'));
    }

    public function testProlongationRefuseeSiLePretEstEnRetard(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule(3, 14, 2));
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->expectException(RegleMetierException::class);
        $this->prets->prolonger($pret, $this->maintenant->modify('+20 days'));
    }

    // ---------- Perdu ----------

    public function testDeclarerPerduCloseLePretEtCreeUneForfait(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule());
        $exemplaire = $this->exemplaire($this->edition(), $site);
        $pret = $this->prets->emprunter($adherent, $exemplaire, $this->maintenant);

        $this->prets->declarerPerdu($pret, $this->maintenant->modify('+1 day'));

        $this->assertNotNull($pret->getDateRetour());
        $this->assertSame(StatutExemplaire::PERDU, $exemplaire->getStatut());
        $penalites = $this->em->getRepository(Penalite::class)->findBy(['pret' => $pret]);
        $this->assertCount(1, $penalites);
        $this->assertSame(MotifPenalite::PERDU, $penalites[0]->getMotif());
        $this->assertSame('50.00', $penalites[0]->getMontant());
    }

    // ---------- Exemplaire mis de côté ----------

    public function testExemplaireMisDeCoteRefuseAUnAutreAdherent(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $beneficiaire = $this->adherent($formule);
        $intrus = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);

        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $this->reservations->reserver($beneficiaire, $edition, $site, $this->maintenant);
        $this->prets->retourner($pret, $site, false, $this->maintenant->modify('+2 days'));

        $this->expectException(RegleMetierException::class);
        $this->prets->emprunter($intrus, $exemplaire, $this->maintenant->modify('+3 days'));
    }

    public function testLeBeneficiaireDeLaReservationPeutEmprunterLExemplaireMisDeCote(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $beneficiaire = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);

        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $reservation = $this->reservations->reserver($beneficiaire, $edition, $site, $this->maintenant);
        $this->prets->retourner($pret, $site, false, $this->maintenant->modify('+2 days'));

        $this->prets->emprunter($beneficiaire, $exemplaire, $this->maintenant->modify('+3 days'));

        $this->assertSame(StatutReservation::HONOREE, $reservation->getStatut());
        $this->assertSame(StatutExemplaire::EMPRUNTE, $exemplaire->getStatut());
    }
}
