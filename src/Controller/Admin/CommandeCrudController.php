<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur CRUD pour la gestion des commandes dans l'administration
 * Accessible uniquement aux utilisateurs avec le rôle ROLE_AGENT ou supérieur
 */
#[IsGranted('ROLE_AGENT')]
class CommandeCrudController extends AbstractCrudController
{
    // Constantes pour les statuts de commande
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_EN_COURS = 'EN_COURS';
    public const STATUT_EXPEDIEE = 'EXPEDIEE';
    public const STATUT_LIVREE = 'LIVREE';
    public const STATUT_ANNULEE = 'ANNULEE';

    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    /**
     * Configuration générale du CRUD
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['dateCommande' => 'DESC'])
            ->setSearchFields(['id', 'user.nom', 'user.prenom', 'user.email'])
            ->setPaginatorPageSize(20);
    }

    /**
     * Configuration des filtres
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut')->setChoices([
                'En attente' => self::STATUT_EN_ATTENTE,
                'En cours' => self::STATUT_EN_COURS,
                'Expédiée' => self::STATUT_EXPEDIEE,
                'Livrée' => self::STATUT_LIVREE,
                'Annulée' => self::STATUT_ANNULEE,
            ]))
            ->add(DateTimeFilter::new('dateCommande'));
    }

    /**
     * Configuration des actions
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Désactiver la création et la suppression
            ->disable(Action::NEW, Action::DELETE)
            // Activer l'action de détail
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * Configuration des champs du CRUD
     */
    public function configureFields(string $pageName): iterable
    {
        // Champs pour la liste (index)
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                IdField::new('id')
                    ->setLabel('N°'),
                
                DateTimeField::new('dateCommande')
                    ->setLabel('Date')
                    ->setFormat('dd/MM/yyyy HH:mm'),
                
                TextField::new('user.email')
                    ->setLabel('Client'),
                
                ChoiceField::new('statut')
                    ->setLabel('Statut')
                    ->setChoices([
                        'En attente' => self::STATUT_EN_ATTENTE,
                        'En cours' => self::STATUT_EN_COURS,
                        'Expédiée' => self::STATUT_EXPEDIEE,
                        'Livrée' => self::STATUT_LIVREE,
                        'Annulée' => self::STATUT_ANNULEE,
                    ])
                    ->renderAsBadges([
                        self::STATUT_EN_ATTENTE => 'warning',
                        self::STATUT_EN_COURS => 'info',
                        self::STATUT_EXPEDIEE => 'primary',
                        self::STATUT_LIVREE => 'success',
                        self::STATUT_ANNULEE => 'danger',
                    ]),
                
                MoneyField::new('montantTotal')
                    ->setCurrency('EUR')
                    ->setLabel('Montant total'),
            ];
        }

        // Champs pour le formulaire d'édition
        if ($pageName === Crud::PAGE_EDIT) {
            return [
                // Informations en lecture seule
                IdField::new('id')
                    ->setLabel('N° Commande')
                    ->setFormTypeOption('disabled', true),
                
                DateTimeField::new('dateCommande')
                    ->setLabel('Date de commande')
                    ->setFormat('dd/MM/yyyy HH:mm')
                    ->setFormTypeOption('disabled', true),
                
                AssociationField::new('user')
                    ->setLabel('Client')
                    ->setFormTypeOption('disabled', true),
                
                MoneyField::new('montantTotal')
                    ->setCurrency('EUR')
                    ->setLabel('Montant total')
                    ->setFormTypeOption('disabled', true),
                
                // Seul champ modifiable : le statut
                ChoiceField::new('statut')
                    ->setLabel('Statut de la commande')
                    ->setChoices([
                        'En attente' => self::STATUT_EN_ATTENTE,
                        'En cours' => self::STATUT_EN_COURS,
                        'Expédiée' => self::STATUT_EXPEDIEE,
                        'Livrée' => self::STATUT_LIVREE,
                        'Annulée' => self::STATUT_ANNULEE,
                    ])
                    ->setRequired(true)
                    ->setHelp('Modifiez le statut pour suivre l\'évolution de la commande'),
                
                // Lignes de commande en lecture seule
                CollectionField::new('ligneCommandes')
                    ->setLabel('Articles commandés')
                    ->setFormTypeOption('disabled', true)
                    ->onlyOnForms(),
            ];
        }

        // Champs pour la page de détail
        if ($pageName === Crud::PAGE_DETAIL) {
            return [
                IdField::new('id')
                    ->setLabel('N° Commande'),
                
                DateTimeField::new('dateCommande')
                    ->setLabel('Date de commande')
                    ->setFormat('dd/MM/yyyy HH:mm'),
                
                AssociationField::new('user')
                    ->setLabel('Client')
                    ->formatValue(function ($value, $entity) {
                        return sprintf(
                            '%s %s (%s)',
                            $entity->getUser()->getNom(),
                            $entity->getUser()->getPrenom(),
                            $entity->getUser()->getEmail()
                        );
                    }),
                
                ChoiceField::new('statut')
                    ->setLabel('Statut')
                    ->setChoices([
                        'En attente' => self::STATUT_EN_ATTENTE,
                        'En cours' => self::STATUT_EN_COURS,
                        'Expédiée' => self::STATUT_EXPEDIEE,
                        'Livrée' => self::STATUT_LIVREE,
                        'Annulée' => self::STATUT_ANNULEE,
                    ])
                    ->renderAsBadges([
                        self::STATUT_EN_ATTENTE => 'warning',
                        self::STATUT_EN_COURS => 'info',
                        self::STATUT_EXPEDIEE => 'primary',
                        self::STATUT_LIVREE => 'success',
                        self::STATUT_ANNULEE => 'danger',
                    ]),
                
                MoneyField::new('montantTotal')
                    ->setCurrency('EUR')
                    ->setLabel('Montant total'),
                
                // Adresse de livraison
                TextareaField::new('adresseLivraison')
                    ->setLabel('Adresse de livraison')
                    ->renderAsHtml(),
                
                TextField::new('codePostalLivraison')
                    ->setLabel('Code postal'),
                
                TextField::new('villeLivraison')
                    ->setLabel('Ville'),
                
                // Lignes de commande avec détails
                CollectionField::new('ligneCommandes')
                    ->setLabel('Articles commandés')
                    ->setTemplatePath('admin/commande_lignes.html.twig')
                    ->formatValue(function ($value, $entity) {
                        $html = '<table class="table table-sm">';
                        $html .= '<thead><tr><th>Livre</th><th>Quantité</th><th>Prix unitaire</th><th>Sous-total</th></tr></thead>';
                        $html .= '<tbody>';
                        
                        foreach ($entity->getLigneCommandes() as $ligne) {
                            $html .= sprintf(
                                '<tr><td>%s</td><td>%d</td><td>%s €</td><td>%s €</td></tr>',
                                $ligne->getLivre()->getTitre(),
                                $ligne->getQuantite(),
                                number_format($ligne->getPrixUnitaire(), 2, ',', ' '),
                                number_format($ligne->getQuantite() * $ligne->getPrixUnitaire(), 2, ',', ' ')
                            );
                        }
                        
                        $html .= '</tbody></table>';
                        return $html;
                    }),
            ];
        }

        // Configuration par défaut
        return [];
    }
}

