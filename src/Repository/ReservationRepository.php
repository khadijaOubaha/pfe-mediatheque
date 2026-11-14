<?php

namespace App\Repository;

use App\Entity\Adherent;
use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Reservation;
use App\Enum\StatutReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /** Tête de la file d'attente : la plus ancienne demande encore en attente. */
    public function premiereEnAttente(Edition $edition): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.edition = :edition')
            ->andWhere('r.statut = :statut')
            ->setParameter('edition', $edition)
            ->setParameter('statut', StatutReservation::EN_ATTENTE->value)
            ->orderBy('r.dateDemande', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function compterEnAttente(Edition $edition): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.edition = :edition')
            ->andWhere('r.statut = :statut')
            ->setParameter('edition', $edition)
            ->setParameter('statut', StatutReservation::EN_ATTENTE->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Réservations encore actives (en attente ou à retirer) de l'adhérent sur l'édition. */
    public function compterActives(Adherent $adherent, Edition $edition): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.adherent = :adherent')
            ->andWhere('r.edition = :edition')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('adherent', $adherent)
            ->setParameter('edition', $edition)
            ->setParameter('statuts', [StatutReservation::EN_ATTENTE->value, StatutReservation::A_RETIRER->value])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Réservation « à retirer » pour laquelle cet exemplaire est mis de côté. */
    public function aRetirerPour(Exemplaire $exemplaire): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exemplaireReserve = :exemplaire')
            ->andWhere('r.statut = :statut')
            ->setParameter('exemplaire', $exemplaire)
            ->setParameter('statut', StatutReservation::A_RETIRER->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Réservations « à retirer » dont la date limite est dépassée.
     *
     * @return list<Reservation>
     */
    public function echues(\DateTimeImmutable $maintenant): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateLimiteRetrait < :maintenant')
            ->setParameter('statut', StatutReservation::A_RETIRER->value)
            ->setParameter('maintenant', $maintenant, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }
}
