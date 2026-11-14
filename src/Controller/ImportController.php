<?php

namespace App\Controller;

use App\Service\ImportIsbnService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_BIBLIOTHECAIRE')]
class ImportController extends AbstractController
{
    private const MAX_PAR_IMPORT = 50;

    #[Route('/import-isbn', name: 'app_import_isbn', methods: ['GET', 'POST'])]
    public function index(Request $request, ImportIsbnService $import): Response
    {
        $saisie = '';
        $resultats = null;

        if ($request->isMethod('POST')) {
            $saisie = (string) $request->request->get('isbns', '');

            if (!$this->isCsrfTokenValid('import_isbn', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');
            } else {
                $liste = $import->extraireIsbns($saisie);
                if ([] === $liste) {
                    $this->addFlash('error', 'Saisissez au moins un ISBN.');
                } elseif (\count($liste) > self::MAX_PAR_IMPORT) {
                    $this->addFlash('error', sprintf('Maximum %d ISBN par import.', self::MAX_PAR_IMPORT));
                } else {
                    $resultats = $import->importerLot($liste);
                }
            }
        }

        return $this->render('import/index.html.twig', ['saisie' => $saisie, 'resultats' => $resultats]);
    }
}
