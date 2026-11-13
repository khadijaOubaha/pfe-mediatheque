<?php

namespace App\Controller\Admin;

use App\Entity\Penalite;
use App\Enum\MotifPenalite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/**
 * @extends AbstractCrudController<Penalite>
 */
class PenaliteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Penalite::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Pénalité')
            ->setEntityLabelInPlural('Pénalités')
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('adherent', 'Adhérent'),
            AssociationField::new('pret', 'Prêt'),
            ChoiceField::new('motif', 'Motif')->setChoices(MotifPenalite::cases()),
            TextField::new('montant', 'Montant (DH)'),
            DateTimeField::new('date', 'Date'),
            BooleanField::new('payee', 'Payée'),
        ];
    }
}
