<?php

namespace App\Controller\Admin;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
{
}

public function index(): Response
{
    return $this->redirect(
        $this->adminUrlGenerator->setController(OeuvreCrudController::class)->generateUrl()
    );
}

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Réseau de médiathèques');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd/MM/yyyy')
            ->setTimeFormat('HH:mm')
            ->setPaginatorPageSize(25);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(OeuvreCrudController::class, 'Œuvres', 'fa fa-book');
        yield MenuItem::linkTo(EditionCrudController::class, 'Éditions', 'fa fa-bookmark');
        yield MenuItem::linkTo(ExemplaireCrudController::class, 'Exemplaires', 'fa fa-barcode');
        yield MenuItem::linkTo(PersonneCrudController::class, 'Personnes', 'fa fa-user-pen');
        yield MenuItem::linkTo(ContributionCrudController::class, 'Contributions', 'fa fa-people-group');

        yield MenuItem::section('Adhérents');
        yield MenuItem::linkTo(AdherentCrudController::class, 'Adhérents', 'fa fa-users');
        yield MenuItem::linkTo(FormuleCrudController::class, 'Formules', 'fa fa-id-card');
        yield MenuItem::linkTo(AbonnementCrudController::class, 'Abonnements', 'fa fa-file-contract');

        yield MenuItem::section('Circulation');
        yield MenuItem::linkTo(PretCrudController::class, 'Prêts', 'fa fa-right-left');
        yield MenuItem::linkTo(ReservationCrudController::class, 'Réservations', 'fa fa-clock');
        yield MenuItem::linkTo(PenaliteCrudController::class, 'Pénalités', 'fa fa-coins');
        yield MenuItem::linkTo(TransfertCrudController::class, 'Transferts', 'fa fa-truck');

        yield MenuItem::section('Configuration');
        yield MenuItem::linkTo(SiteCrudController::class, 'Sites', 'fa fa-building');
    }
}
