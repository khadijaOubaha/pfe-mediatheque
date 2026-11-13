<?php

namespace App\Controller\Admin;

use App\Entity\Exemplaire;
use App\Enum\EtatExemplaire;
use App\Enum\StatutExemplaire;
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
 * @extends AbstractCrudController<Exemplaire>
 */
class ExemplaireCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Exemplaire::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Exemplaire')
            ->setEntityLabelInPlural('Exemplaires')
            ->setSearchFields(['codeBarres'])
            ->setDefaultSort(['codeBarres' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('codeBarres', 'Code-barres'),
            AssociationField::new('edition', 'Édition'),
            AssociationField::new('siteCourant', 'Site actuel'),
            AssociationField::new('siteOrigine', 'Site d\'origine')->hideOnIndex(),
            ChoiceField::new('etat', 'État')->setChoices(EtatExemplaire::cases()),
            ChoiceField::new('statut', 'Statut')->setChoices(StatutExemplaire::cases()),
        ];
    }
}
