<?php

namespace App\Repository;

use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Site;
use App\Enum\StatutExemplaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exemplaire>
 */
class ExemplaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exemplaire::class);
    }

    public function compterDisponiblesSurSite(Edition $edition, Site $site): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.edition = :edition')
            ->andWhere('e.siteCourant = :site')
            ->andWhere('e.statut = :statut')
            ->setParameter('edition', $edition)
            ->setParameter('site', $site)
            ->setParameter('statut', StatutExemplaire::DISPONIBLE->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Un exemplaire disponible de l'édition, sur n'importe quel site. */
    public function premierDisponible(Edition $edition): ?Exemplaire
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.edition = :edition')
            ->andWhere('e.statut = :statut')
            ->setParameter('edition', $edition)
            ->setParameter('statut', StatutExemplaire::DISPONIBLE->value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
