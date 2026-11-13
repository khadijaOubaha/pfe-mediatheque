<?php

namespace App\Enum;

enum RoleContribution: string
{
    case AUTEUR = 'AUTEUR';
    case TRADUCTEUR = 'TRADUCTEUR';
    case ILLUSTRATEUR = 'ILLUSTRATEUR';
    case REALISATEUR = 'REALISATEUR';
}
