<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_apres_connexion');
        }

        return $this->render('security/login.html.twig', [
            'dernier_email' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Cette méthode est interceptée par le pare-feu.');
    }

    /** Redirige l'utilisateur connecté vers l'écran qui correspond à son rôle. */
    #[Route('/apres-connexion', name: 'app_apres_connexion')]
    public function apresConnexion(): Response
    {
        if ($this->isGranted('ROLE_BIBLIOTHECAIRE')) {
            return $this->redirect('/admin');
        }
        if ($this->isGranted('ROLE_ADHERENT')) {
            return $this->redirectToRoute('app_espace');
        }

        return $this->redirectToRoute('app_catalogue');
    }
}
