<?php

namespace App\Repository;

use App\Entity\Edition;
use App\Entity\Exemplaire;
use App\Entity\Oeuvre;
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

    /**
     * Pour chaque édition de l'œuvre et chaque site : exemplaires en circulation et exemplaires disponibles.
     *
     * @return list<array{edition_id: int, site: string, total: int, disponibles: int}>
     */
    public function disponibilitesParOeuvre(Oeuvre $oeuvre): array
    {
        $lignes = $this->getEntityManager()->createQuery(
            'SELECT ed.id AS edition_id, s.nom AS site, COUNT(e.id) AS total,
                    SUM(CASE WHEN e.statut = :dispo THEN 1 ELSE 0 END) AS disponibles
             FROM App\Entity\Exemplaire e
             JOIN e.siteCourant s
             JOIN e.edition ed
             WHERE ed.oeuvre = :oeuvre AND e.statut NOT IN (:hors)
             GROUP BY ed.id, s.id, s.nom
             ORDER BY s.nom ASC'
        )
            ->setParameter('oeuvre', $oeuvre)
            ->setParameter('dispo', StatutExemplaire::DISPONIBLE->value)
            ->setParameter('hors', [StatutExemplaire::PERDU->value, StatutExemplaire::RETIRE->value])
            ->getArrayResult();

        return array_map(static fn (array $l): array => [
            'edition_id' => (int) $l['edition_id'],
            'site' => $l['site'],
            'total' => (int) $l['total'],
            'disponibles' => (int) $l['disponibles'],
        ], $lignes);
    }
}
