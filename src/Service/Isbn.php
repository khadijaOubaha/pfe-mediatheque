<?php

namespace App\Service;

/**
 * Validation et normalisation des ISBN. Tout ISBN valide est ramené au format ISBN-13 sans tirets.
 */
final class Isbn
{
    private function __construct()
    {
    }

    /** @return string|null l'ISBN-13 normalisé, ou null si la saisie n'est pas un ISBN valide */
    public static function normaliser(string $saisie): ?string
    {
        $brut = strtoupper(preg_replace('/[\s\-]/', '', $saisie) ?? '');

        if (1 === preg_match('/^\d{13}$/', $brut)) {
            return self::estValide13($brut) ? $brut : null;
        }
        if (1 === preg_match('/^\d{9}[\dX]$/', $brut)) {
            return self::estValide10($brut) ? self::convertir10vers13($brut) : null;
        }

        return null;
    }

    private static function estValide13(string $isbn): bool
    {
        $somme = 0;
        foreach (str_split($isbn) as $position => $chiffre) {
            $somme += (int) $chiffre * (0 === $position % 2 ? 1 : 3);
        }

        return 0 === $somme % 10;
    }

    private static function estValide10(string $isbn): bool
    {
        $somme = 0;
        foreach (str_split($isbn) as $position => $caractere) {
            $valeur = 'X' === $caractere ? 10 : (int) $caractere;
            $somme += $valeur * (10 - $position);
        }

        return 0 === $somme % 11;
    }

    private static function convertir10vers13(string $isbn10): string
    {
        $base = '978'.substr($isbn10, 0, 9);
        $somme = 0;
        foreach (str_split($base) as $position => $chiffre) {
            $somme += (int) $chiffre * (0 === $position % 2 ? 1 : 3);
        }

        return $base.((10 - $somme % 10) % 10);
    }
}
