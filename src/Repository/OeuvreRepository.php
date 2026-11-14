<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use App\Enum\TypeOeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    /**
     * Recherche dans le titre, l'ISBN, l'éditeur et les contributeurs (auteurs, traducteurs...).
     *
     * @return array{0: list<Oeuvre>, 1: int} la page de résultats et le nombre total
     */
    public function rechercher(string $q, ?TypeOeuvre $type, int $page, int $parPage): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.editions', 'e')
            ->leftJoin('e.contributions', 'c')
            ->leftJoin('c.personne', 'p')
            ->orderBy('o.titre', 'ASC');

        if ('' !== $q) {
            $qb->andWhere('o.titre LIKE :q OR o.titreOriginal LIKE :q OR e.isbn LIKE :q OR e.editeur LIKE :q OR p.nom LIKE :q OR p.prenom LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }
        if (null !== $type) {
            $qb->andWhere('o.type = :type')->setParameter('type', $type->value);
        }

        $qb->setFirstResult(($page - 1) * $parPage)->setMaxResults($parPage);
        $paginator = new Paginator($qb, true);

        return [iterator_to_array($paginator), count($paginator)];
    }
}
