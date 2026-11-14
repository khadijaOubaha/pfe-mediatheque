<?php

namespace App\Controller;

use App\Service\StatistiquesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_BIBLIOTHECAIRE')]
class StatistiquesController extends AbstractController
{
    #[Route('/statistiques', name: 'app_stats')]
    public function index(StatistiquesService $statistiques): Response
    {
        return $this->render('statistiques/index.html.twig', [
            'plus_empruntes' => $statistiques->plusEmpruntes(10),
            'jamais_empruntes' => $statistiques->jamaisEmpruntes(50),
            'nb_jamais_empruntes' => $statistiques->compterJamaisEmpruntes(),
            'rotation' => $statistiques->tauxDeRotation(),
        ]);
    }
}
