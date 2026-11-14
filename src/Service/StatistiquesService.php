<?php

namespace App\Service;

use App\Enum\StatutExemplaire;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Statistiques de circulation : documents les plus empruntés, jamais empruntés, taux de rotation.
 */
class StatistiquesService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** @return list<array{id: int, titre: string, nb: int}> */
    public function plusEmpruntes(int $limite = 10): array
    {
        $lignes = $this->em->createQuery(
            'SELECT o.id AS id, o.titre AS titre, COUNT(p.id) AS nb
             FROM App\Entity\Pret p
             JOIN p.exemplaire e
             JOIN e.edition ed
             JOIN ed.oeuvre o
             GROUP BY o.id, o.titre
             ORDER BY nb DESC, o.titre ASC'
        )->setMaxResults($limite)->getArrayResult();

        return array_map(static fn (array $l): array => ['id' => (int) $l['id'], 'titre' => $l['titre'], 'nb' => (int) $l['nb']], $lignes);
    }

    /** @return list<array{id: int, titre: string}> */
    public function jamaisEmpruntes(int $limite = 50): array
    {
        return $this->em->createQuery(
            'SELECT o.id AS id, o.titre AS titre
             FROM App\Entity\Oeuvre o
             WHERE NOT EXISTS (
                 SELECT p.id FROM App\Entity\Pret p
                 JOIN p.exemplaire e
                 JOIN e.edition ed
                 WHERE ed.oeuvre = o
             )
             ORDER BY o.titre ASC'
        )->setMaxResults($limite)->getArrayResult();
    }

    public function compterJamaisEmpruntes(): int
    {
        return (int) $this->em->createQuery(
            'SELECT COUNT(o.id)
             FROM App\Entity\Oeuvre o
             WHERE NOT EXISTS (
                 SELECT p.id FROM App\Entity\Pret p
                 JOIN p.exemplaire e
                 JOIN e.edition ed
                 WHERE ed.oeuvre = o
             )'
        )->getSingleScalarResult();
    }

    /**
     * Taux de rotation sur les 12 derniers mois : nombre de prêts divisé par le nombre d'exemplaires en circulation.
     *
     * @return array{global: array{prets: int, exemplaires: int, taux: float}, sites: list<array{site: string, prets: int, exemplaires: int, taux: float}>}
     */
    public function tauxDeRotation(?\DateTimeImmutable $maintenant = null): array
    {
        $depuis = ($maintenant ?? new \DateTimeImmutable())->modify('-12 months');
        $hors = [StatutExemplaire::PERDU->value, StatutExemplaire::RETIRE->value];

        $pretsParSite = $this->em->createQuery(
            'SELECT s.nom AS site, COUNT(p.id) AS nb
             FROM App\Entity\Pret p JOIN p.siteEmprunt s
             WHERE p.dateEmprunt >= :depuis
             GROUP BY s.id, s.nom'
        )->setParameter('depuis', $depuis, Types::DATETIME_IMMUTABLE)->getArrayResult();

        $exemplairesParSite = $this->em->createQuery(
            'SELECT s.nom AS site, COUNT(e.id) AS nb
             FROM App\Entity\Exemplaire e JOIN e.siteCourant s
             WHERE e.statut NOT IN (:hors)
             GROUP BY s.id, s.nom
             ORDER BY s.nom ASC'
        )->setParameter('hors', $hors)->getArrayResult();

        $prets = [];
        foreach ($pretsParSite as $ligne) {
            $prets[$ligne['site']] = (int) $ligne['nb'];
        }

        $sites = [];
        $totalPrets = 0;
        $totalExemplaires = 0;
        foreach ($exemplairesParSite as $ligne) {
            $nbPrets = $prets[$ligne['site']] ?? 0;
            $nbExemplaires = (int) $ligne['nb'];
            $sites[] = [
                'site' => $ligne['site'],
                'prets' => $nbPrets,
                'exemplaires' => $nbExemplaires,
                'taux' => $nbExemplaires > 0 ? round($nbPrets / $nbExemplaires, 2) : 0.0,
            ];
            $totalPrets += $nbPrets;
            $totalExemplaires += $nbExemplaires;
        }

        return [
            'global' => [
                'prets' => $totalPrets,
                'exemplaires' => $totalExemplaires,
                'taux' => $totalExemplaires > 0 ? round($totalPrets / $totalExemplaires, 2) : 0.0,
            ],
            'sites' => $sites,
        ];
    }
}
