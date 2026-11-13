<?php

namespace App\Controller\Admin;

use App\Entity\Pret;
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
 * @extends AbstractCrudController<Pret>
 */
class PretCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Pret::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Prêt')
            ->setEntityLabelInPlural('Prêts')
            ->setDefaultSort(['dateEmprunt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('exemplaire', 'Exemplaire'),
            AssociationField::new('adherent', 'Adhérent'),
            AssociationField::new('siteEmprunt', 'Site')->hideOnIndex(),
            DateTimeField::new('dateEmprunt', 'Emprunté le'),
            DateField::new('dateEcheance', 'Échéance'),
            DateTimeField::new('dateRetour', 'Rendu le'),
            IntegerField::new('nbProlongations', 'Prolongations'),
        ];
    }
}
