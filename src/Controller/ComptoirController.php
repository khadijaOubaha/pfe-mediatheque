<?php

namespace App\Controller;

use App\Enum\StatutExemplaire;
use App\Exception\RegleMetierException;
use App\Repository\AdherentRepository;
use App\Repository\ExemplaireRepository;
use App\Repository\PenaliteRepository;
use App\Repository\PretRepository;
use App\Repository\SiteRepository;
use App\Service\PretService;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Comptoir du bibliothécaire : enregistre les prêts et les retours en passant par les services métier
 * (donc avec le contrôle de toutes les règles : limites, retards, file d'attente, pénalités).
 */
#[IsGranted('ROLE_BIBLIOTHECAIRE')]
#[Route('/comptoir')]
class ComptoirController extends AbstractController
{
    #[Route('', name: 'app_comptoir', methods: ['GET'])]
    public function index(PretRepository $prets, SiteRepository $sites): Response
    {
        return $this->render('comptoir/index.html.twig', [
            'sites' => $sites->findAll(),
            'prets_ouverts' => $prets->findBy(['dateRetour' => null], ['dateEcheance' => 'ASC'], 20),
            'aujourdhui' => new \DateTimeImmutable('today'),
        ]);
    }

    #[Route('/emprunter', name: 'app_comptoir_emprunter', methods: ['POST'])]
    public function emprunter(Request $request, AdherentRepository $adherents, ExemplaireRepository $exemplaires, PretService $service): Response
    {
        $this->verifierJeton($request);

        $adherent = $adherents->findOneBy(['email' => trim((string) $request->request->get('email'))]);
        $exemplaire = $exemplaires->findOneBy(['codeBarres' => trim((string) $request->request->get('code_barres'))]);
        if (null === $adherent) {
            $this->addFlash('error', 'Aucun adhérent avec cet email.');
        } elseif (null === $exemplaire) {
            $this->addFlash('error', 'Aucun exemplaire avec ce code-barres.');
        } else {
            try {
                $pret = $service->emprunter($adherent, $exemplaire);
                $this->addFlash('success', sprintf('Prêt enregistré : %s emprunte « %s », à rendre le %s.', $adherent, $exemplaire->getEdition(), $pret->getDateEcheance()->format('d/m/Y')));
            } catch (RegleMetierException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_comptoir');
    }

    #[Route('/retourner', name: 'app_comptoir_retourner', methods: ['POST'])]
    public function retourner(
        Request $request,
        ExemplaireRepository $exemplaires,
        PretRepository $prets,
        SiteRepository $sites,
        PenaliteRepository $penalites,
        PretService $service,
    ): Response {
        $this->verifierJeton($request);

        $exemplaire = $exemplaires->findOneBy(['codeBarres' => trim((string) $request->request->get('code_barres'))]);
        $pret = null === $exemplaire ? null : $prets->findOneBy(['exemplaire' => $exemplaire, 'dateRetour' => null]);
        $site = $sites->find($request->request->getInt('site'));

        if (null === $exemplaire) {
            $this->addFlash('error', 'Aucun exemplaire avec ce code-barres.');
        } elseif (null === $pret) {
            $this->addFlash('error', "Cet exemplaire n'est pas actuellement en prêt.");
        } else {
            try {
                $service->retourner($pret, $site, $request->request->getBoolean('abime'));

                $suite = match ($exemplaire->getStatut()) {
                    StatutExemplaire::MIS_DE_COTE => ' Exemplaire mis de côté pour un adhérent en attente.',
                    StatutExemplaire::EN_TRANSIT => ' Exemplaire à transférer vers un autre site pour une réservation.',
                    StatutExemplaire::RETIRE => ' Exemplaire retiré de la circulation.',
                    default => ' Exemplaire de nouveau disponible.',
                };
                foreach ($penalites->findBy(['pret' => $pret]) as $penalite) {
                    $suite .= sprintf(' Pénalité %s : %s DH.', $penalite->getMotif()->value, $penalite->getMontant());
                }
                $this->addFlash('success', sprintf('Retour enregistré pour « %s ».', $exemplaire->getEdition()).$suite);
            } catch (RegleMetierException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_comptoir');
    }

    #[Route('/expirer-reservations', name: 'app_comptoir_expirer', methods: ['POST'])]
    public function expirer(Request $request, ReservationService $reservations): Response
    {
        $this->verifierJeton($request);

        $nombre = $reservations->expirerEchues();
        $this->addFlash('success', sprintf('%d réservation(s) expirée(s).', $nombre));

        return $this->redirectToRoute('app_comptoir');
    }

    private function verifierJeton(Request $request): void
    {
        if (!$this->isCsrfTokenValid('comptoir', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
    }
}
