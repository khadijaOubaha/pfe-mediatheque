<?php

namespace App\Repository;

use App\Entity\Adherent;
use App\Entity\Edition;
use App\Entity\Pret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pret>
 */
class PretRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pret::class);
    }

    /** Nombre de prêts non rendus de l'adhérent. */
    public function compterEnCours(Adherent $adherent): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.adherent = :adherent')
            ->andWhere('p.dateRetour IS NULL')
            ->setParameter('adherent', $adherent)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Nombre de prêts non rendus dont l'échéance est strictement dépassée. */
    public function compterEnRetard(Adherent $adherent, \DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.adherent = :adherent')
            ->andWhere('p.dateRetour IS NULL')
            ->andWhere('p.dateEcheance < :date')
            ->setParameter('adherent', $adherent)
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** L'adhérent a-t-il déjà un exemplaire de cette édition en prêt ? */
    public function adherentAEditionEnCours(Adherent $adherent, Edition $edition): bool
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.exemplaire', 'e')
            ->andWhere('p.adherent = :adherent')
            ->andWhere('e.edition = :edition')
            ->andWhere('p.dateRetour IS NULL')
            ->setParameter('adherent', $adherent)
            ->setParameter('edition', $edition)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
