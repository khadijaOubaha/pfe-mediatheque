<?php

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\Exemplaire;
use App\Entity\Formule;
use App\Entity\Penalite;
use App\Entity\Pret;
use App\Entity\Site;
use App\Enum\EtatExemplaire;
use App\Enum\MotifPenalite;
use App\Enum\StatutExemplaire;
use App\Enum\StatutReservation;
use App\Exception\RegleMetierException;
use App\Repository\AbonnementRepository;
use App\Repository\PretRepository;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prêts : emprunter, retourner, prolonger, déclarer perdu.
 * Chaque opération s'exécute dans une transaction, avec un verrou sur l'exemplaire.
 */
class PretService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AbonnementRepository $abonnements,
        private readonly PretRepository $prets,
        private readonly ReservationRepository $reservations,
        private readonly ReservationService $reservationService,
    ) {
    }

    public function emprunter(Adherent $adherent, Exemplaire $exemplaire, ?\DateTimeImmutable $maintenant = null): Pret
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($adherent, $exemplaire, $maintenant): Pret {
            // Verrou : un même exemplaire ne peut pas être prêté deux fois en parallèle.
            $this->em->refresh($exemplaire, LockMode::PESSIMISTIC_WRITE);

            $abonnement = $this->abonnements->actifLe($adherent, $maintenant);
            if (null === $abonnement) {
                throw new RegleMetierException("L'adhérent n'a pas d'abonnement en cours de validité.");
            }
            $formule = $abonnement->getFormule();

            if ($this->prets->compterEnCours($adherent) >= $formule->getMaxPrets()) {
                throw new RegleMetierException(sprintf('Limite de %d prêts simultanés atteinte pour la formule %s.', $formule->getMaxPrets(), $formule));
            }
            if ($this->prets->compterEnRetard($adherent, $maintenant) > 0) {
                throw new RegleMetierException("L'adhérent a un prêt en retard : il doit le rendre avant d'emprunter.");
            }

            $reservationHonoree = null;
            switch ($exemplaire->getStatut()) {
                case StatutExemplaire::MIS_DE_COTE:
                    $reservationHonoree = $this->reservations->aRetirerPour($exemplaire);
                    if (null === $reservationHonoree || $reservationHonoree->getAdherent() !== $adherent) {
                        throw new RegleMetierException('Cet exemplaire est mis de côté pour un autre adhérent.');
                    }
                    break;
                case StatutExemplaire::DISPONIBLE:
                    $premiere = $this->reservations->premiereEnAttente($exemplaire->getEdition());
                    if (null !== $premiere && $premiere->getAdherent() !== $adherent) {
                        throw new RegleMetierException("Des réservations attendent cette édition : l'exemplaire est réservé au premier de la file.");
                    }
                    $reservationHonoree = $premiere;
                    break;
                default:
                    throw new RegleMetierException(sprintf("Cet exemplaire n'est pas empruntable (statut %s).", $exemplaire->getStatut()->value));
            }

            $pret = (new Pret())
                ->setExemplaire($exemplaire)
                ->setAdherent($adherent)
                ->setSiteEmprunt($exemplaire->getSiteCourant())
                ->setDateEmprunt($maintenant)
                ->setDateEcheance($maintenant->modify(sprintf('+%d days', $formule->getDureePretJours()))->setTime(0, 0));
            $this->em->persist($pret);

            $exemplaire->setStatut(StatutExemplaire::EMPRUNTE);
            $reservationHonoree?->setStatut(StatutReservation::HONOREE);

            return $pret;
        });
    }

    /**
     * Enregistre le retour d'un exemplaire : pénalité de retard éventuelle,
     * service de la file d'attente, ou remise en circulation.
     */
    public function retourner(Pret $pret, ?Site $siteRetour = null, bool $abime = false, ?\DateTimeImmutable $maintenant = null): Pret
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($pret, $siteRetour, $abime, $maintenant): Pret {
            if (null !== $pret->getDateRetour()) {
                throw new RegleMetierException('Ce prêt a déjà été rendu.');
            }

            $exemplaire = $pret->getExemplaire();
            $this->em->refresh($exemplaire, LockMode::PESSIMISTIC_WRITE);

            $pret->setDateRetour($maintenant);
            $exemplaire->setSiteCourant($siteRetour ?? $exemplaire->getSiteCourant());

            $jourRetour = $maintenant->setTime(0, 0);
            if ($jourRetour > $pret->getDateEcheance()) {
                $joursRetard = (int) $pret->getDateEcheance()->diff($jourRetour)->days;
                $tarif = (float) $this->formuleDuPret($pret)->getPenaliteParJour();
                $this->creerPenalite($pret, MotifPenalite::RETARD, number_format($joursRetard * $tarif, 2, '.', ''), $maintenant);
            }

            if ($abime) {
                $exemplaire->setEtat(EtatExemplaire::ABIME)->setStatut(StatutExemplaire::RETIRE);
                $this->creerPenalite($pret, MotifPenalite::ABIME, Regles::FORFAIT_ABIME, $maintenant);
            } else {
                $this->reservationService->servirOuLiberer($exemplaire, $maintenant);
            }

            return $pret;
        });
    }

    /** Prolonge un prêt si personne n'attend, s'il n'est pas en retard et si le quota n'est pas atteint. */
    public function prolonger(Pret $pret, ?\DateTimeImmutable $maintenant = null): Pret
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($pret, $maintenant): Pret {
            if (null !== $pret->getDateRetour()) {
                throw new RegleMetierException('Ce prêt a déjà été rendu.');
            }
            if ($pret->getDateEcheance() < $maintenant->setTime(0, 0)) {
                throw new RegleMetierException('Un prêt en retard ne peut pas être prolongé.');
            }

            $formule = $this->formuleDuPret($pret);
            if ($pret->getNbProlongations() >= $formule->getMaxProlongations()) {
                throw new RegleMetierException(sprintf('Nombre maximal de prolongations atteint (%d).', $formule->getMaxProlongations()));
            }
            if ($this->reservations->compterEnAttente($pret->getExemplaire()->getEdition()) > 0) {
                throw new RegleMetierException("Des adhérents attendent cette édition : le prêt ne peut pas être prolongé.");
            }

            $pret->setDateEcheance($pret->getDateEcheance()->modify(sprintf('+%d days', $formule->getDureePretJours())));
            $pret->setNbProlongations($pret->getNbProlongations() + 1);

            return $pret;
        });
    }

    /** Déclare l'exemplaire perdu : le prêt est clos et une pénalité forfaitaire est créée. */
    public function declarerPerdu(Pret $pret, ?\DateTimeImmutable $maintenant = null): Pret
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->em->wrapInTransaction(function () use ($pret, $maintenant): Pret {
            if (null !== $pret->getDateRetour()) {
                throw new RegleMetierException('Ce prêt a déjà été rendu.');
            }

            $pret->setDateRetour($maintenant);
            $pret->getExemplaire()->setStatut(StatutExemplaire::PERDU);
            $this->creerPenalite($pret, MotifPenalite::PERDU, Regles::FORFAIT_PERDU, $maintenant);

            return $pret;
        });
    }

    /** Formule qui s'appliquait à l'adhérent à la date d'emprunt. */
    private function formuleDuPret(Pret $pret): Formule
    {
        $abonnement = $this->abonnements->actifLe($pret->getAdherent(), $pret->getDateEmprunt());
        if (null === $abonnement) {
            throw new RegleMetierException("Aucun abonnement ne couvrait la date d'emprunt de ce prêt.");
        }

        return $abonnement->getFormule();
    }

    private function creerPenalite(Pret $pret, MotifPenalite $motif, string $montant, \DateTimeImmutable $date): Penalite
    {
        $penalite = (new Penalite())
            ->setAdherent($pret->getAdherent())
            ->setPret($pret)
            ->setMotif($motif)
            ->setMontant($montant)
            ->setDate($date)
            ->setPayee(false);
        $this->em->persist($penalite);

        return $penalite;
    }
}
