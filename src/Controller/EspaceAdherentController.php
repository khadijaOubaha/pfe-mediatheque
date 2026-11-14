<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Entity\Pret;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\StatutReservation;
use App\Exception\RegleMetierException;
use App\Repository\PenaliteRepository;
use App\Repository\PretRepository;
use App\Repository\ReservationRepository;
use App\Service\PretService;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADHERENT')]
class EspaceAdherentController extends AbstractController
{
    #[Route('/mon-espace', name: 'app_espace')]
    public function index(PretRepository $prets, ReservationRepository $reservations, PenaliteRepository $penalites): Response
    {
        $adherent = $this->adherent();

        $tousLesPrets = $prets->findBy(['adherent' => $adherent], ['dateEmprunt' => 'DESC']);
        $toutesLesReservations = $reservations->findBy(['adherent' => $adherent], ['dateDemande' => 'DESC']);
        $estActive = static fn (Reservation $r): bool => \in_array($r->getStatut(), [StatutReservation::EN_ATTENTE, StatutReservation::A_RETIRER], true);

        return $this->render('espace/index.html.twig', [
            'adherent' => $adherent,
            'prets_en_cours' => array_values(array_filter($tousLesPrets, static fn (Pret $p): bool => null === $p->getDateRetour())),
            'historique' => array_values(array_filter($tousLesPrets, static fn (Pret $p): bool => null !== $p->getDateRetour())),
            'reservations_actives' => array_values(array_filter($toutesLesReservations, $estActive)),
            'reservations_passees' => array_values(array_filter($toutesLesReservations, static fn (Reservation $r): bool => !$estActive($r))),
            'penalites' => $penalites->findBy(['adherent' => $adherent], ['date' => 'DESC']),
            'aujourdhui' => new \DateTimeImmutable('today'),
        ]);
    }

    #[Route('/mon-espace/pret/{id}/prolonger', name: 'app_pret_prolonger', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function prolonger(Pret $pret, Request $request, PretService $service): Response
    {
        $this->verifierJeton($request, 'prolonger'.$pret->getId());
        if ($pret->getAdherent() !== $this->adherent()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $service->prolonger($pret);
            $this->addFlash('success', 'Votre prêt a été prolongé.');
        } catch (RegleMetierException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_espace');
    }

    #[Route('/mon-espace/reservation/{id}/annuler', name: 'app_reservation_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annuler(Reservation $reservation, Request $request, ReservationService $service): Response
    {
        $this->verifierJeton($request, 'annuler'.$reservation->getId());
        if ($reservation->getAdherent() !== $this->adherent()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $service->annuler($reservation);
            $this->addFlash('success', 'Votre réservation a été annulée.');
        } catch (RegleMetierException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_espace');
    }

    private function adherent(): Adherent
    {
        $user = $this->getUser();
        if (!$user instanceof User || null === $user->getAdherent()) {
            throw $this->createAccessDeniedException("Aucune fiche adhérent n'est associée à ce compte.");
        }

        return $user->getAdherent();
    }

    private function verifierJeton(Request $request, string $identifiant): void
    {
        if (!$this->isCsrfTokenValid($identifiant, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }
    }
}
