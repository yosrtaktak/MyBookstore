<?php

namespace App\Controller\Admin;

use App\Entity\Auteur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
class AuteurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Auteur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Auteur')
            ->setEntityLabelInPlural('Auteurs')
            ->setSearchFields(['prenom', 'nom', 'nationalite', 'biographie'])
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', 'Liste des %entity_label_plural%')
            ->setPageTitle('new', 'Créer un %entity_label_singular%')
            ->setPageTitle('edit', 'Modifier %entity_as_string%')
            ->setPageTitle('detail', 'Détails de %entity_as_string%');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_AGENT')
            ->setPermission(Action::EDIT, 'ROLE_AGENT')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_AGENT');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('nom', 'Nom'))
            ->add(TextFilter::new('prenom', 'Prénom'))
            ->add(TextFilter::new('nationalite', 'Nationalité'))
            ->add(DateTimeFilter::new('dateNaissance', 'Date de naissance'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('prenom')
                ->setLabel('Prénom')
                ->setHelp('Prénom de l\'auteur')
                ->setColumns(6),
            TextField::new('nom')
                ->setLabel('Nom')
                ->setHelp('Nom de famille de l\'auteur')
                ->setColumns(6),
            TextField::new('nationalite')
                ->setLabel('Nationalité')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            DateField::new('dateNaissance')
                ->setLabel('Date de naissance')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            TextEditorField::new('biographie')
                ->setLabel('Biographie')
                ->setRequired(false)
                ->hideOnIndex()
                ->setColumns(12),
            AssociationField::new('livres')
                ->setLabel('Livres')
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    return count($entity->getLivres()) . ' livre(s)';
                }),
        ];
    }
}
