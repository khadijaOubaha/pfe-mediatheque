<?php

namespace App\Tests\Service;

use App\Entity\Transfert;
use App\Enum\StatutExemplaire;
use App\Enum\StatutReservation;
use App\Exception\RegleMetierException;
use App\Tests\DatabaseTestCase;

class ReservationServiceTest extends DatabaseTestCase
{
    public function testReservationRefuseeSiUnExemplaireEstDisponibleSurLeSiteDeRetrait(): void
    {
        $site = $this->site();
        $adherent = $this->adherent($this->formule());
        $edition = $this->edition();
        $this->exemplaire($edition, $site);

        $this->expectException(RegleMetierException::class);
        $this->reservations->reserver($adherent, $edition, $site, $this->maintenant);
    }

    public function testReservationRefuseeSansAbonnement(): void
    {
        $siteA = $this->site('A');
        $siteB = $this->site('B');
        $adherent = $this->adherent(null);
        $edition = $this->edition();
        $this->exemplaire($edition, $siteB);

        $this->expectException(RegleMetierException::class);
        $this->reservations->reserver($adherent, $edition, $siteA, $this->maintenant);
    }

    public function testDeuxReservationsActivesSurLaMemeEditionRefusees(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $attendant = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);
        $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $this->reservations->reserver($attendant, $edition, $site, $this->maintenant);

        $this->expectException(RegleMetierException::class);
        $this->reservations->reserver($attendant, $edition, $site, $this->maintenant->modify('+1 hour'));
    }

    public function testReservationDUnExemplaireDeLAutreSiteDeclencheUnTransfert(): void
    {
        $siteA = $this->site('A');
        $siteB = $this->site('B');
        $adherent = $this->adherent($this->formule());
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $siteB);

        $reservation = $this->reservations->reserver($adherent, $edition, $siteA, $this->maintenant);

        $this->assertSame(StatutReservation::A_RETIRER, $reservation->getStatut());
        $this->assertSame(StatutExemplaire::EN_TRANSIT, $exemplaire->getStatut());
        $transfert = $this->em->getRepository(Transfert::class)->findOneBy(['exemplaire' => $exemplaire]);
        $this->assertNotNull($transfert);
        $this->assertSame($siteB, $transfert->getSiteOrigine());
        $this->assertSame($siteA, $transfert->getSiteDestination());

        $this->reservations->receptionnerTransfert($transfert, $this->maintenant->modify('+1 day'));

        $this->assertSame(StatutExemplaire::MIS_DE_COTE, $exemplaire->getStatut());
        $this->assertSame($siteA, $exemplaire->getSiteCourant());
        $this->assertNotNull($transfert->getDateReception());
    }

    public function testReservationExpireQuandLaDateLimiteDeRetraitEstDepassee(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $reservant = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);

        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $reservation = $this->reservations->reserver($reservant, $edition, $site, $this->maintenant);
        $this->prets->retourner($pret, $site, false, $this->maintenant->modify('+3 days'));
        $this->assertSame(StatutReservation::A_RETIRER, $reservation->getStatut());

        $nombre = $this->reservations->expirerEchues($this->maintenant->modify('+10 days'));

        $this->assertSame(1, $nombre);
        $this->assertSame(StatutReservation::EXPIREE, $reservation->getStatut());
        $this->assertSame(StatutExemplaire::DISPONIBLE, $exemplaire->getStatut());
    }

    public function testAnnulerUneReservationLibereLExemplaireMisDeCote(): void
    {
        $site = $this->site();
        $formule = $this->formule();
        $emprunteur = $this->adherent($formule);
        $reservant = $this->adherent($formule);
        $edition = $this->edition();
        $exemplaire = $this->exemplaire($edition, $site);

        $pret = $this->prets->emprunter($emprunteur, $exemplaire, $this->maintenant);
        $reservation = $this->reservations->reserver($reservant, $edition, $site, $this->maintenant);
        $this->prets->retourner($pret, $site, false, $this->maintenant->modify('+3 days'));

        $this->reservations->annuler($reservation, $this->maintenant->modify('+4 days'));

        $this->assertSame(StatutReservation::ANNULEE, $reservation->getStatut());
        $this->assertSame(StatutExemplaire::DISPONIBLE, $exemplaire->getStatut());
    }
}
