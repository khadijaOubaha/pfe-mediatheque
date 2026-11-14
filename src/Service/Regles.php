<?php

namespace App\Service;

/**
 * Constantes des règles métier, faciles à ajuster (voir docs/DECISIONS.md).
 */
final class Regles
{
    /** Durée pendant laquelle un exemplaire réservé reste mis de côté. */
    public const JOURS_MISE_DE_COTE = 5;

    /** Pénalité forfaitaire pour un exemplaire perdu (en DH). */
    public const FORFAIT_PERDU = '50.00';

    /** Pénalité forfaitaire pour un exemplaire rendu abîmé (en DH). */
    public const FORFAIT_ABIME = '20.00';

    private function __construct()
    {
    }
}
