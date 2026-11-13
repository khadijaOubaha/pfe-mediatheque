<?php

namespace App\Enum;

enum StatutExemplaire: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case EMPRUNTE = 'EMPRUNTE';
    case MIS_DE_COTE = 'MIS_DE_COTE';
    case EN_TRANSIT = 'EN_TRANSIT';
    case PERDU = 'PERDU';
    case RETIRE = 'RETIRE';
}
