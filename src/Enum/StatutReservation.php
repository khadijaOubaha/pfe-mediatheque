<?php

namespace App\Enum;

enum StatutReservation: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case A_RETIRER = 'A_RETIRER';
    case HONOREE = 'HONOREE';
    case ANNULEE = 'ANNULEE';
    case EXPIREE = 'EXPIREE';
}
