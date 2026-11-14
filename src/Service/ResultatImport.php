<?php

namespace App\Service;

use App\Entity\Edition;
use App\Enum\StatutImport;

/** Résultat de l'import d'un ISBN. */
final class ResultatImport
{
    /** @param list<string> $differences écarts constatés entre le catalogue et Open Library */
    public function __construct(
        public readonly string $isbn,
        public readonly StatutImport $statut,
        public readonly string $message = '',
        public readonly array $differences = [],
        public readonly ?Edition $edition = null,
    ) {
    }
}
