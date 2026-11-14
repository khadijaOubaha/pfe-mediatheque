<?php

namespace App\Exception;

/**
 * Levée quand une opération viole une règle métier (limite de prêts, exemplaire indisponible, etc.).
 */
class RegleMetierException extends \DomainException
{
}
