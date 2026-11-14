<?php

namespace App\Service;

use App\Entity\Contribution;
use App\Entity\Edition;
use App\Entity\Oeuvre;
use App\Entity\Personne;
use App\Enum\RoleContribution;
use App\Enum\StatutImport;
use App\Enum\TypeOeuvre;
use App\Exception\FournisseurIndisponibleException;
use App\Repository\EditionRepository;
use App\Repository\OeuvreRepository;
use App\Repository\PersonneRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée ou complète des fiches du catalogue à partir d'ISBN.
 *
 * Règles (voir docs/DECISIONS.md) : le catalogue local fait foi. Une fiche existante n'est jamais écrasée,
 * seuls ses champs vides sont complétés, et les écarts sont signalés. Un échec sur un ISBN n'interrompt pas un lot.
 */
class ImportIsbnService
{
    private const PARTICULES = ['de', 'du', 'des', 'la', 'le', 'van', 'von', 'der', 'den', 'ben', 'el', 'al', 'bin', 'ibn'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FournisseurFicheIsbn $fournisseur,
        private readonly EditionRepository $editions,
        private readonly OeuvreRepository $oeuvres,
        private readonly PersonneRepository $personnes,
    ) {
    }

    /** @return list<string> les ISBN d'un texte libre (séparés par des espaces, retours à la ligne, virgules ou points-virgules) */
    public function extraireIsbns(string $texte): array
    {
        $morceaux = preg_split('/[\s,;]+/', trim($texte), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($morceaux));
    }

    /**
     * @param iterable<string> $saisies
     *
     * @return list<ResultatImport>
     */
    public function importerLot(iterable $saisies): array
    {
        $resultats = [];
        $vus = [];
        foreach ($saisies as $saisie) {
            $cle = Isbn::normaliser($saisie) ?? $saisie;
            if (isset($vus[$cle])) {
                continue;
            }
            $vus[$cle] = true;
            $resultats[] = $this->importer($saisie);
        }

        return $resultats;
    }

    public function importer(string $saisie): ResultatImport
    {
        $isbn = Isbn::normaliser($saisie);
        if (null === $isbn) {
            return new ResultatImport($saisie, StatutImport::INVALIDE, "Ce n'est pas un ISBN valide.");
        }

        try {
            $fiche = $this->fournisseur->chercher($isbn);
        } catch (FournisseurIndisponibleException $e) {
            return new ResultatImport($isbn, StatutImport::ECHEC, $e->getMessage());
        }

        if (null === $fiche) {
            return new ResultatImport($isbn, StatutImport::INTROUVABLE, "Open Library ne connaît pas cet ISBN : la fiche est à saisir à la main.");
        }

        $existante = $this->editions->findOneBy(['isbn' => $isbn]);

        return null === $existante ? $this->creer($isbn, $fiche) : $this->completer($existante, $fiche);
    }

    /** @param array{titre: string, auteurs: list<string>, editeur: ?string, annee: ?int, nbPages: ?int, couverture: ?string} $fiche */
    private function creer(string $isbn, array $fiche): ResultatImport
    {
        $oeuvre = $this->oeuvres->findOneBy(['titre' => $fiche['titre']]);
        $rattachee = null !== $oeuvre;
        if (null === $oeuvre) {
            $oeuvre = (new Oeuvre())->setTitre($fiche['titre'])->setType(TypeOeuvre::LIVRE);
            $this->em->persist($oeuvre);
        }

        $edition = (new Edition())
            ->setOeuvre($oeuvre)
            ->setIsbn($isbn)
            ->setEditeur($fiche['editeur'])
            ->setAnneePublication($fiche['annee'])
            ->setNbPages($fiche['nbPages'])
            ->setCouvertureUrl($fiche['couverture'])
            ->setLangue('fr');
        $this->em->persist($edition);

        foreach ($fiche['auteurs'] as $nomComplet) {
            $contribution = (new Contribution())
                ->setPersonne($this->trouverOuCreerPersonne($nomComplet))
                ->setRole(RoleContribution::AUTEUR);
            $edition->addContribution($contribution);
            $this->em->persist($contribution);
        }

        $this->em->flush();

        $message = $rattachee
            ? "Édition créée et rattachée à l'œuvre existante."
            : 'Fiche créée (nouvelle œuvre et nouvelle édition).';

        return new ResultatImport($isbn, StatutImport::CREEE, $message, [], $edition);
    }

    /** @param array{titre: string, auteurs: list<string>, editeur: ?string, annee: ?int, nbPages: ?int, couverture: ?string} $fiche */
    private function completer(Edition $edition, array $fiche): ResultatImport
    {
        $remplis = [];
        $differences = [];

        if (null !== $fiche['editeur']) {
            if (null === $edition->getEditeur() || '' === $edition->getEditeur()) {
                $edition->setEditeur($fiche['editeur']);
                $remplis[] = 'éditeur';
            } elseif (0 !== strcasecmp($edition->getEditeur(), $fiche['editeur'])) {
                $differences[] = sprintf('Éditeur : catalogue « %s », Open Library « %s »', $edition->getEditeur(), $fiche['editeur']);
            }
        }

        if (null !== $fiche['annee']) {
            if (null === $edition->getAnneePublication()) {
                $edition->setAnneePublication($fiche['annee']);
                $remplis[] = 'année';
            } elseif ($edition->getAnneePublication() !== $fiche['annee']) {
                $differences[] = sprintf('Année : catalogue %d, Open Library %d', $edition->getAnneePublication(), $fiche['annee']);
            }
        }

        if (null !== $fiche['nbPages']) {
            if (null === $edition->getNbPages()) {
                $edition->setNbPages($fiche['nbPages']);
                $remplis[] = 'nombre de pages';
            } elseif ($edition->getNbPages() !== $fiche['nbPages']) {
                $differences[] = sprintf('Pages : catalogue %d, Open Library %d', $edition->getNbPages(), $fiche['nbPages']);
            }
        }

        if (null !== $fiche['couverture'] && (null === $edition->getCouvertureUrl() || '' === $edition->getCouvertureUrl())) {
            $edition->setCouvertureUrl($fiche['couverture']);
            $remplis[] = 'couverture';
        }

        $titreCatalogue = (string) $edition->getOeuvre()?->getTitre();
        if (0 !== strcasecmp($titreCatalogue, $fiche['titre'])) {
            $differences[] = sprintf('Titre : catalogue « %s », Open Library « %s »', $titreCatalogue, $fiche['titre']);
        }

        if ([] === $remplis) {
            return new ResultatImport((string) $edition->getIsbn(), StatutImport::INCHANGEE, 'La fiche existe déjà : rien à compléter.', $differences, $edition);
        }

        $this->em->flush();

        return new ResultatImport(
            (string) $edition->getIsbn(),
            StatutImport::COMPLETEE,
            'Champs complétés : '.implode(', ', $remplis).'.',
            $differences,
            $edition,
        );
    }

    private function trouverOuCreerPersonne(string $nomComplet): Personne
    {
        $morceaux = preg_split('/\s+/', trim($nomComplet), -1, PREG_SPLIT_NO_EMPTY) ?: [$nomComplet];
        $nom = array_pop($morceaux);
        // Les particules (de, van, ben...) font partie du nom de famille.
        while ([] !== $morceaux && \in_array(mb_strtolower(end($morceaux)), self::PARTICULES, true)) {
            $nom = array_pop($morceaux).' '.$nom;
        }
        $prenom = [] === $morceaux ? null : implode(' ', $morceaux);

        $personne = $this->personnes->findOneBy(['nom' => $nom, 'prenom' => $prenom]);
        if (null === $personne) {
            $personne = (new Personne())->setNom($nom)->setPrenom($prenom);
            $this->em->persist($personne);
        }

        return $personne;
    }
}
