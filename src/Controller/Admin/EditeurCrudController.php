<?php

namespace App\Controller\Admin;

use App\Entity\Editeur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
class EditeurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Editeur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Éditeur')
            ->setEntityLabelInPlural('Éditeurs')
            ->setSearchFields(['nomEditeur', 'pays', 'email', 'adresse'])
            ->setDefaultSort(['nomEditeur' => 'ASC'])
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
            ->add(TextFilter::new('nomEditeur', 'Nom de l\'éditeur'))
            ->add(TextFilter::new('pays', 'Pays'))
            ->add(TextFilter::new('email', 'Email'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('nomEditeur')
                ->setLabel('Nom de l\'éditeur')
                ->setHelp('Nom officiel de la maison d\'édition')
                ->setColumns(6),
            TextField::new('pays')
                ->setLabel('Pays')
                ->setRequired(false)
                ->setColumns(6),
            TextField::new('adresse')
                ->setLabel('Adresse')
                ->setRequired(false)
                ->hideOnIndex()
                ->setColumns(12),
            EmailField::new('email')
                ->setLabel('Email')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            TelephoneField::new('telephone')
                ->setLabel('Téléphone')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            UrlField::new('siteWeb')
                ->setLabel('Site web')
                ->setRequired(false)
                ->setColumns(12)
                ->hideOnIndex(),
            AssociationField::new('livres')
                ->setLabel('Livres')
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    return count($entity->getLivres()) . ' livre(s)';
                }),
        ];
    }
}
