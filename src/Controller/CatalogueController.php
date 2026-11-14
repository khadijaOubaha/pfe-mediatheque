<?php

namespace App\Controller;

use App\Entity\Edition;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\TypeOeuvre;
use App\Exception\RegleMetierException;
use App\Repository\ExemplaireRepository;
use App\Repository\OeuvreRepository;
use App\Repository\SiteRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CatalogueController extends AbstractController
{
    private const PAR_PAGE = 24;

    #[Route('/', name: 'app_home')]
    public function accueil(): Response
    {
        return $this->redirectToRoute('app_catalogue');
    }

    #[Route('/catalogue', name: 'app_catalogue')]
    public function index(Request $request, OeuvreRepository $oeuvres): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = TypeOeuvre::tryFrom((string) $request->query->get('type', ''));
        $page = max(1, $request->query->getInt('page', 1));

        [$liste, $total] = $oeuvres->rechercher($q, $type, $page, self::PAR_PAGE);

        return $this->render('catalogue/index.html.twig', [
            'oeuvres' => $liste,
            'total' => $total,
            'q' => $q,
            'type' => $type?->value ?? '',
            'types' => TypeOeuvre::cases(),
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PAR_PAGE)),
        ]);
    }

    #[Route('/catalogue/{id}', name: 'app_catalogue_show', requirements: ['id' => '\d+'])]
    public function show(Oeuvre $oeuvre, ExemplaireRepository $exemplaires, SiteRepository $sites): Response
    {
        $disponibilites = [];
        foreach ($exemplaires->disponibilitesParOeuvre($oeuvre) as $ligne) {
            $disponibilites[$ligne['edition_id']][] = $ligne;
        }

        return $this->render('catalogue/show.html.twig', [
            'oeuvre' => $oeuvre,
            'disponibilites' => $disponibilites,
            'sites' => $sites->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADHERENT')]
    #[Route('/edition/{id}/reserver', name: 'app_reserver', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reserver(Edition $edition, Request $request, SiteRepository $sites, ReservationService $reservations): Response
    {
        $oeuvreId = $edition->getOeuvre()->getId();
        $retour = $this->redirectToRoute('app_catalogue_show', ['id' => $oeuvreId]);

        if (!$this->isCsrfTokenValid('reserver'.$edition->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $retour;
        }

        /** @var User $user */
        $user = $this->getUser();
        $adherent = $user->getAdherent();
        $site = $sites->find($request->request->getInt('site'));
        if (null === $adherent || null === $site) {
            $this->addFlash('error', 'Réservation impossible : adhérent ou site introuvable.');

            return $retour;
        }

        try {
            $reservations->reserver($adherent, $edition, $site);
            $this->addFlash('success', 'Votre réservation est enregistrée.');
        } catch (RegleMetierException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $retour;
    }
}
