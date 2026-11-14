<?php

namespace App\Service;

use App\Exception\FournisseurIndisponibleException;

interface FournisseurFicheIsbn
{
    /**
     * Cherche la fiche d'un ISBN-13.
     *
     * @return array{titre: string, auteurs: list<string>, editeur: ?string, annee: ?int, nbPages: ?int, couverture: ?string}|null
     *                                                                                                                              null si l'ISBN est inconnu du fournisseur
     *
     * @throws FournisseurIndisponibleException si le fournisseur est injoignable
     */
    public function chercher(string $isbn): ?array;
}
