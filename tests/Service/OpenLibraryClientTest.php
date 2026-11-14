<?php

namespace App\Tests\Service;

use App\Exception\FournisseurIndisponibleException;
use App\Service\OpenLibraryClient;
use PHPUnit\Framework\TestCase;

class OpenLibraryClientTest extends TestCase
{
    /** Client dont le téléchargement est remplacé par une réponse fixe (aucun appel réseau). */
    private function client(string $reponse): OpenLibraryClient
    {
        return new class($reponse) extends OpenLibraryClient {
            public function __construct(private readonly string $reponse)
            {
            }

            protected function telecharger(string $url): string
            {
                return $this->reponse;
            }
        };
    }

    public function testLaFicheEstConvertieDansLeFormatDuCatalogue(): void
    {
        $json = json_encode(['ISBN:9782070612758' => [
            'title' => 'Le Petit Prince',
            'authors' => [['name' => 'Antoine de Saint-Exupéry']],
            'publishers' => [['name' => 'Gallimard']],
            'publish_date' => 'March 2, 2007',
            'number_of_pages' => 93,
            'cover' => ['large' => 'https://covers.openlibrary.org/b/id/1-L.jpg'],
        ]]);

        $fiche = $this->client($json)->chercher('9782070612758');

        $this->assertSame('Le Petit Prince', $fiche['titre']);
        $this->assertSame(['Antoine de Saint-Exupéry'], $fiche['auteurs']);
        $this->assertSame('Gallimard', $fiche['editeur']);
        $this->assertSame(2007, $fiche['annee']);
        $this->assertSame(93, $fiche['nbPages']);
        $this->assertSame('https://covers.openlibrary.org/b/id/1-L.jpg', $fiche['couverture']);
    }

    public function testLesChampsAbsentsDonnentNull(): void
    {
        $json = json_encode(['ISBN:9782070612758' => ['title' => 'Titre seul']]);

        $fiche = $this->client($json)->chercher('9782070612758');

        $this->assertSame('Titre seul', $fiche['titre']);
        $this->assertSame([], $fiche['auteurs']);
        $this->assertNull($fiche['editeur']);
        $this->assertNull($fiche['annee']);
        $this->assertNull($fiche['nbPages']);
        $this->assertNull($fiche['couverture']);
    }

    public function testUnIsbnInconnuDonneNull(): void
    {
        $this->assertNull($this->client('{}')->chercher('9782070612758'));
    }

    public function testUneReponseIllisibleLeveUneExceptionDIndisponibilite(): void
    {
        $this->expectException(FournisseurIndisponibleException::class);
        $this->client('<html>erreur</html>')->chercher('9782070612758');
    }
}
