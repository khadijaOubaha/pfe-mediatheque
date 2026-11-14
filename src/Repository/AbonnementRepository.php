<?php

namespace App\Repository;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    /** Abonnement valide à la date donnée (le plus tardif s'il y en a plusieurs). */
    public function actifLe(Adherent $adherent, \DateTimeImmutable $date): ?Abonnement
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.adherent = :adherent')
            ->andWhere('a.dateDebut <= :date')
            ->andWhere('a.dateFin >= :date')
            ->setParameter('adherent', $adherent)
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->orderBy('a.dateFin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
