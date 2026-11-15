<?php

namespace App\Service;

use App\Exception\FournisseurIndisponibleException;

/**
 * Client de l'API publique Open Library (https://openlibrary.org/dev/docs/api/books), sans clé.
 */
class OpenLibraryClient implements FournisseurFicheIsbn
{
    private const URL = 'https://openlibrary.org/api/books?bibkeys=ISBN:%s&format=json&jscmd=data';

    public function chercher(string $isbn): ?array
    {
        $json = $this->telecharger(sprintf(self::URL, rawurlencode($isbn)));

        try {
            $donnees = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new FournisseurIndisponibleException('Réponse Open Library illisible.', 0, $e);
        }
        if (!\is_array($donnees)) {
            throw new FournisseurIndisponibleException('Réponse Open Library inattendue.');
        }

        $fiche = $donnees['ISBN:'.$isbn] ?? null;
        if (!\is_array($fiche) || '' === trim((string) ($fiche['title'] ?? ''))) {
            return null;
        }

        $auteurs = [];
        foreach ($fiche['authors'] ?? [] as $auteur) {
            $nom = trim((string) ($auteur['name'] ?? ''));
            if ('' !== $nom) {
                $auteurs[] = $nom;
            }
        }

        $editeur = trim((string) ($fiche['publishers'][0]['name'] ?? ''));
        $annee = preg_match_all('/\d{4}/', (string) ($fiche['publish_date'] ?? ''), $correspondances) > 0
            ? (int) end($correspondances[0])
            : null;
        $pages = (int) ($fiche['number_of_pages'] ?? 0);
        $couverture = $fiche['cover']['large'] ?? $fiche['cover']['medium'] ?? null;

        return [
            'titre' => trim((string) $fiche['title']),
            'auteurs' => $auteurs,
            'editeur' => '' !== $editeur ? $editeur : null,
            'annee' => $annee,
            'nbPages' => $pages > 0 ? $pages : null,
            'couverture' => \is_string($couverture) ? $couverture : null,
        ];
    }

    /** Télécharge une URL. Méthode isolée pour pouvoir être remplacée dans les tests. */
    protected function telecharger(string $url): string
    {
        $contexte = stream_context_create(['http' => [
            'timeout' => 10,
            'header' => "User-Agent: mediatheque-pfe/1.0 (projet etudiant)\r\nAccept: application/json\r\n",
        ]]);

        $reponse = @file_get_contents($url, false, $contexte);
        // Courte pause pour rester courtois avec l'API lors des imports par lot.
        usleep(200000);

        if (false === $reponse) {
            $detail = error_get_last()['message'] ?? 'erreur inconnue';

            throw new FournisseurIndisponibleException(sprintf('Open Library est injoignable (%s).', $detail));
        }

        return $reponse;
    }
}
