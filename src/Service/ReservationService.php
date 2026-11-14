<?php

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Reservation;
use App\Entity\Site;
use App\Entity\Transfert;
use App\Enum\StatutExemplaire;
use App\Enum\StatutReservation;
use App\Exception\RegleMetierException;
use App\Repository\AbonnementRepository;
use App\Repository\ExemplaireRepository;
use App\Repository\PretRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Réservations, file d'attente, mise de côté et transferts entre sites.
 */
class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AbonnementRepository $abonnements,
        private readonly PretRepository $prets,
        private readonly ReservationRepository $reservations,
        private readonly ExemplaireRepository $exemplaires,
    ) {
    }

    /**
     * Crée une réservation. Si un exemplaire est disponible sur un autre site,
     * il est tout de suite affecté (avec transfert vers le site de retrait).
     */
    public function reserver(Adherent $adherent, Edition $edition, Site $siteRetrait, ?\DateTimeImmutable $maintenant = null): Reservation
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($adherent, $edition, $siteRetrait, $maintenant): Reservation {
            if (null === $this->abonnements->actifLe($adherent, $maintenant)) {
                throw new RegleMetierException("L'adhérent n'a pas d'abonnement en cours de validité.");
            }
            if ($this->prets->adherentAEditionEnCours($adherent, $edition)) {
                throw new RegleMetierException("L'adhérent a déjà cette édition en prêt.");
            }
            if ($this->reservations->compterActives($adherent, $edition) > 0) {
                throw new RegleMetierException("L'adhérent a déjà une réservation active sur cette édition.");
            }
            if ($this->exemplaires->compterDisponiblesSurSite($edition, $siteRetrait) > 0) {
                throw new RegleMetierException('Un exemplaire est déjà disponible sur ce site : il peut être emprunté directement.');
            }

            $reservation = (new Reservation())
                ->setEdition($edition)
                ->setAdherent($adherent)
                ->setSiteRetrait($siteRetrait)
                ->setDateDemande($maintenant)
                ->setStatut(StatutReservation::EN_ATTENTE);
            $this->em->persist($reservation);

            // Un exemplaire disponible ailleurs : on le fait venir tout de suite.
            $disponible = $this->exemplaires->premierDisponible($edition);
            if (null !== $disponible) {
                $this->servir($reservation, $disponible, $maintenant);
            }

            return $reservation;
        });
    }

    /** Annule une réservation active et libère l'exemplaire mis de côté, le cas échéant. */
    public function annuler(Reservation $reservation, ?\DateTimeImmutable $maintenant = null): void
    {
        $maintenant ??= new \DateTimeImmutable();

        $this->em->wrapInTransaction(function () use ($reservation, $maintenant): void {
            $statut = $reservation->getStatut();
            if (StatutReservation::EN_ATTENTE !== $statut && StatutReservation::A_RETIRER !== $statut) {
                throw new RegleMetierException('Cette réservation n\'est plus active.');
            }

            $exemplaire = $reservation->getExemplaireReserve();
            $reservation->setStatut(StatutReservation::ANNULEE);

            if (StatutReservation::A_RETIRER === $statut && null !== $exemplaire
                && StatutExemplaire::MIS_DE_COTE === $exemplaire->getStatut()) {
                $this->servirOuLiberer($exemplaire, $maintenant);
            }
        });
    }

    /**
     * Passe en « expirées » les réservations dont la date limite de retrait est dépassée
     * et propose l'exemplaire au réservant suivant (ou le remet en circulation).
     *
     * @return int nombre de réservations expirées
     */
    public function expirerEchues(?\DateTimeImmutable $maintenant = null): int
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($maintenant): int {
            $echues = $this->reservations->echues($maintenant);
            foreach ($echues as $reservation) {
                $reservation->setStatut(StatutReservation::EXPIREE);
                $exemplaire = $reservation->getExemplaireReserve();
                if (null !== $exemplaire && StatutExemplaire::MIS_DE_COTE === $exemplaire->getStatut()) {
                    $this->servirOuLiberer($exemplaire, $maintenant);
                }
            }

            return \count($echues);
        });
    }

    /** Enregistre l'arrivée d'un exemplaire transféré sur son site de destination. */
    public function receptionnerTransfert(Transfert $transfert, ?\DateTimeImmutable $maintenant = null): void
    {
        $maintenant ??= new \DateTimeImmutable();

        $this->em->wrapInTransaction(function () use ($transfert, $maintenant): void {
            if (null !== $transfert->getDateReception()) {
                throw new RegleMetierException('Ce transfert a déjà été réceptionné.');
            }

            $exemplaire = $transfert->getExemplaire();
            $transfert->setDateReception($maintenant);
            $exemplaire->setSiteCourant($transfert->getSiteDestination());

            if (null !== $this->reservations->aRetirerPour($exemplaire)) {
                $exemplaire->setStatut(StatutExemplaire::MIS_DE_COTE);
            } else {
                $this->servirOuLiberer($exemplaire, $maintenant);
            }
        });
    }

    /**
     * Affecte un exemplaire libre à la première réservation en attente de son édition,
     * sinon le remet à disposition.
     */
    public function servirOuLiberer(Exemplaire $exemplaire, \DateTimeImmutable $maintenant): void
    {
        // On enregistre les changements en cours pour que la requête voie l'état à jour.
        $this->em->flush();

        $premiere = $this->reservations->premiereEnAttente($exemplaire->getEdition());
        if (null !== $premiere) {
            $this->servir($premiere, $exemplaire, $maintenant);
        } else {
            $exemplaire->setStatut(StatutExemplaire::DISPONIBLE);
        }
    }

    /** Met l'exemplaire de côté pour la réservation (avec transfert si nécessaire). */
    public function servir(Reservation $reservation, Exemplaire $exemplaire, \DateTimeImmutable $maintenant): void
    {
        $reservation
            ->setStatut(StatutReservation::A_RETIRER)
            ->setExemplaireReserve($exemplaire)
            ->setDateLimiteRetrait($maintenant->modify(sprintf('+%d days', Regles::JOURS_MISE_DE_COTE)));

        if ($exemplaire->getSiteCourant() !== $reservation->getSiteRetrait()) {
            $transfert = (new Transfert())
                ->setExemplaire($exemplaire)
                ->setSiteOrigine($exemplaire->getSiteCourant())
                ->setSiteDestination($reservation->getSiteRetrait())
                ->setDateEnvoi($maintenant);
            $this->em->persist($transfert);
            $exemplaire->setStatut(StatutExemplaire::EN_TRANSIT);
        } else {
            $exemplaire->setStatut(StatutExemplaire::MIS_DE_COTE);
        }
    }
}
