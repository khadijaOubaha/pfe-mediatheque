<?php

namespace App\Enum;

enum StatutImport: string
{
    case CREEE = 'CREEE';
    case COMPLETEE = 'COMPLETEE';
    case INCHANGEE = 'INCHANGEE';
    case INTROUVABLE = 'INTROUVABLE';
    case INVALIDE = 'INVALIDE';
    case ECHEC = 'ECHEC';
}
