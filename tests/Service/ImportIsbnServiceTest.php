<?php

namespace App\Tests\Service;

use App\Entity\Contribution;
use App\Entity\Edition;
use App\Entity\Oeuvre;
use App\Enum\StatutImport;
use App\Exception\FournisseurIndisponibleException;
use App\Repository\EditionRepository;
use App\Repository\OeuvreRepository;
use App\Repository\PersonneRepository;
use App\Entity\Personne;
use App\Service\FournisseurFicheIsbn;
use App\Service\ImportIsbnService;
use App\Tests\DatabaseTestCase;

class ImportIsbnServiceTest extends DatabaseTestCase
{
    private const PETIT_PRINCE = '9782070612758';
    private const AUTRE = '9780306406157';

    /** @param array<string, array<string, mixed>|\Throwable|null> $fiches fiches renvoyées par ISBN */
    private function service(array $fiches): ImportIsbnService
    {
        $fournisseur = new class($fiches) implements FournisseurFicheIsbn {
            public function __construct(private readonly array $fiches)
            {
            }

            public function chercher(string $isbn): ?array
            {
                $valeur = $this->fiches[$isbn] ?? null;
                if ($valeur instanceof \Throwable) {
                    throw $valeur;
                }

                return $valeur;
            }
        };

        /** @var EditionRepository $editions */
        $editions = $this->em->getRepository(Edition::class);
        /** @var OeuvreRepository $oeuvres */
        $oeuvres = $this->em->getRepository(Oeuvre::class);
        /** @var PersonneRepository $personnes */
        $personnes = $this->em->getRepository(Personne::class);

        return new ImportIsbnService($this->em, $fournisseur, $editions, $oeuvres, $personnes);
    }

    /** @return array<string, mixed> */
    private function fichePetitPrince(): array
    {
        return [
            'titre' => 'Le Petit Prince',
            'auteurs' => ['Antoine de Saint-Exupéry'],
            'editeur' => 'Gallimard',
            'annee' => 2007,
            'nbPages' => 93,
            'couverture' => 'https://covers.openlibrary.org/b/id/1-L.jpg',
        ];
    }

    public function testCreeUneFicheComplete(): void
    {
        $resultat = $this->service([self::PETIT_PRINCE => $this->fichePetitPrince()])->importer(self::PETIT_PRINCE);

        $this->assertSame(StatutImport::CREEE, $resultat->statut);
        $edition = $resultat->edition;
        $this->assertSame(self::PETIT_PRINCE, $edition->getIsbn());
        $this->assertSame('Gallimard', $edition->getEditeur());
        $this->assertSame(2007, $edition->getAnneePublication());
        $this->assertSame(93, $edition->getNbPages());
        $this->assertSame('Le Petit Prince', $edition->getOeuvre()->getTitre());

        $contributions = $this->em->getRepository(Contribution::class)->findBy(['edition' => $edition]);
        $this->assertCount(1, $contributions);
        $this->assertSame('Antoine', $contributions[0]->getPersonne()->getPrenom());
        $this->assertSame('de Saint-Exupéry', $contributions[0]->getPersonne()->getNom());
    }

    public function testUnIsbnEnFormeDixChiffresEstAccepte(): void
    {
        $resultat = $this->service([self::PETIT_PRINCE => $this->fichePetitPrince()])->importer('2-07-061275-9');

        $this->assertSame(StatutImport::CREEE, $resultat->statut);
        $this->assertSame(self::PETIT_PRINCE, $resultat->edition->getIsbn());
    }

    public function testUneNouvelleEditionEstRattacheeALOeuvreExistante(): void
    {
        $oeuvre = (new Oeuvre())->setTitre('Le Petit Prince')->setType(\App\Enum\TypeOeuvre::LIVRE);
        $this->em->persist($oeuvre);
        $this->em->flush();

        $resultat = $this->service([self::PETIT_PRINCE => $this->fichePetitPrince()])->importer(self::PETIT_PRINCE);

        $this->assertSame(StatutImport::CREEE, $resultat->statut);
        $this->assertSame($oeuvre, $resultat->edition->getOeuvre());
        $this->assertCount(1, $this->em->getRepository(Oeuvre::class)->findBy(['titre' => 'Le Petit Prince']));
    }

    public function testUneFicheExistanteEstCompleteSansEtreEcrasee(): void
    {
        $edition = $this->edition();
        $edition->setIsbn(self::PETIT_PRINCE)->setEditeur('Folio');
        $this->em->flush();

        $resultat = $this->service([self::PETIT_PRINCE => $this->fichePetitPrince()])->importer(self::PETIT_PRINCE);

        $this->assertSame(StatutImport::COMPLETEE, $resultat->statut);
        // Champs vides complétés...
        $this->assertSame(93, $edition->getNbPages());
        $this->assertSame(2007, $edition->getAnneePublication());
        // ... mais la valeur du catalogue est conservée, et l'écart est signalé.
        $this->assertSame('Folio', $edition->getEditeur());
        $this->assertNotEmpty(array_filter($resultat->differences, static fn (string $d): bool => str_contains($d, 'Éditeur')));
    }

    public function testUneFicheDejaCompleteResteInchangee(): void
    {
        $edition = $this->edition();
        $edition->setIsbn(self::PETIT_PRINCE)->setEditeur('Gallimard')->setAnneePublication(2007)->setNbPages(93)
            ->setCouvertureUrl('https://covers.openlibrary.org/b/id/1-L.jpg');
        $edition->getOeuvre()->setTitre('Le Petit Prince');
        $this->em->flush();

        $resultat = $this->service([self::PETIT_PRINCE => $this->fichePetitPrince()])->importer(self::PETIT_PRINCE);

        $this->assertSame(StatutImport::INCHANGEE, $resultat->statut);
        $this->assertSame([], $resultat->differences);
    }

    public function testUnIsbnInvalideEstRefuseSansAppelAuFournisseur(): void
    {
        $resultat = $this->service([])->importer('1234567890123');

        $this->assertSame(StatutImport::INVALIDE, $resultat->statut);
    }

    public function testUnIsbnInconnuDOpenLibraryEstSignale(): void
    {
        $resultat = $this->service([self::PETIT_PRINCE => null])->importer(self::PETIT_PRINCE);

        $this->assertSame(StatutImport::INTROUVABLE, $resultat->statut);
    }

    public function testUneIndisponibiliteNInterrompPasLeLot(): void
    {
        $service = $this->service([
            self::AUTRE => new FournisseurIndisponibleException('Service coupé.'),
            self::PETIT_PRINCE => $this->fichePetitPrince(),
        ]);

        $resultats = $service->importerLot([self::AUTRE, 'pas-un-isbn', self::PETIT_PRINCE, self::PETIT_PRINCE]);

        // Le doublon est ignoré ; les trois lignes distinctes sont traitées.
        $this->assertCount(3, $resultats);
        $this->assertSame(StatutImport::ECHEC, $resultats[0]->statut);
        $this->assertSame(StatutImport::INVALIDE, $resultats[1]->statut);
        $this->assertSame(StatutImport::CREEE, $resultats[2]->statut);
    }

    public function testExtractionDesIsbnDUnTexteLibre(): void
    {
        $liste = $this->service([])->extraireIsbns("9782070612758, 9780306406157\n9782070612758;2070612759");

        $this->assertSame(['9782070612758', '9780306406157', '2070612759'], $liste);
    }
}
