<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Contrôleur CRUD pour la gestion des livres dans l'administration
 */
class LivreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Livre::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Livre')
            ->setEntityLabelInPlural('Livres')
            ->setSearchFields(['titre', 'isbn', 'auteurs.nom', 'editeur.nomEditeur'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(15)
            ->setPageTitle('index', '📚 Catalogue des livres');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée pour voir la description dans un modal
        $viewDescription = Action::new('viewDescription', 'Description', 'tabler:eye')
            ->linkToCrudAction('viewDescription')
            ->setCssClass('btn btn-info btn-sm');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewDescription);
    }

    public function configureFields(string $pageName): iterable
    {
        // Pour INDEX - afficher tous les champs importants
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                ImageField::new('imageCouverture')
                    ->setBasePath('/uploads/livres')
                    ->setLabel('📖'),
                TextField::new('titre')
                    ->setLabel('Titre'),
                TextField::new('isbn')
                    ->setLabel('ISBN'),
                AssociationField::new('auteurs')
                    ->setLabel('Auteur(s)')
                    ->formatValue(function ($value, $entity) {
                        $auteurs = $entity->getAuteurs();
                        return $auteurs->count() > 0 
                            ? implode(', ', $auteurs->map(fn($a) => $a->getNom())->toArray())
                            : '-';
                    }),
                AssociationField::new('editeur')
                    ->setLabel('Éditeur'),
                AssociationField::new('categories')
                    ->setLabel('Catégorie(s)')
                    ->formatValue(function ($value, $entity) {
                        $categories = $entity->getCategories();
                        return $categories->count() > 0 
                            ? implode(', ', $categories->map(fn($c) => $c->getLibelle())->toArray())
                            : '-';
                    }),
                TextField::new('description')
                    ->setLabel('Description')
                    ->formatValue(function ($value, $entity) {
                        $description = $entity->getDescription() ?? '';
                        $modalId = 'descModal' . $entity->getId();
                        
                        if (empty($description)) {
                            return '-';
                        }
                        
                        return sprintf(
                            '<a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#%s">
                                <i class="fa fa-eye"></i> View content
                            </a>
                            
                            <!-- Modal -->
                            <div class="modal fade" id="%s" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); color: white;">
                                            <h5 class="modal-title"><i class="fa fa-book"></i> %s</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div style="line-height: 1.8;">%s</div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>',
                            $modalId,
                            $modalId,
                            htmlspecialchars($entity->getTitre()),
                            $description
                        );
                    })
                    ->renderAsHtml(),
                MoneyField::new('prix')
                    ->setCurrency('EUR')
                    ->setLabel('Prix'),
                IntegerField::new('stock')
                    ->setLabel('Stock'),
                IntegerField::new('nbPages')
                    ->setLabel('Pages'),
                TextField::new('langue')
                    ->setLabel('Langue'),
                DateField::new('dateEdition')
                    ->setLabel('Date édition'),
            ];
        }

        // Champs communs pour NEW, EDIT, DETAIL
        $fields = [
            // Informations générales
            TextField::new('titre')
                ->setLabel('Titre')
                ->setColumns(8)
                ->setRequired(true),
            
            TextField::new('isbn')
                ->setLabel('ISBN')
                ->setColumns(4)
                ->setHelp('Format: XXX-X-XXXX-XXXX-X'),
        ];

        // Image de couverture
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = ImageField::new('imageCouverture')
                ->setBasePath('/uploads/livres')
                ->setLabel('Couverture');
        } else {
            $fields[] = TextField::new('imageFile')
                ->setFormType(VichImageType::class)
                ->setLabel('Image de couverture')
                ->setRequired(false)
                ->setHelp('Formats acceptés: JPG, PNG, WEBP (max 2MB)');
        }

        // Description - différent selon la page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = TextEditorField::new('description')
                ->setLabel('Description');
        } else {
            // Formulaires NEW et EDIT
            $fields[] = TextEditorField::new('description')
                ->setLabel('Description')
                ->setRequired(false)
                ->setColumns(12)
                ->setHelp('Utilisez l\'éditeur pour formater le texte (gras, italique, listes, etc.)');
        }

        // Reste des champs
        return array_merge($fields, [
            IntegerField::new('nbPages')
                ->setLabel('Nombre de pages')
                ->setColumns(4),
            
            TextField::new('langue')
                ->setLabel('Langue')
                ->setColumns(4)
                ->setHelp('Ex: Français, Anglais, Arabe'),
            
            DateField::new('dateEdition')
                ->setLabel('Date d\'édition')
                ->setColumns(4)
                ->setRequired(false),
            
            // Prix et stock
            MoneyField::new('prix')
                ->setCurrency('EUR')
                ->setLabel('Prix unitaire')
                ->setColumns(4),
            
            IntegerField::new('stock')
                ->setLabel('Stock disponible')
                ->setColumns(4)
                ->setHelp('Quantité en stock'),
            
            IntegerField::new('nbExemplaires')
                ->setLabel('Nombre d\'exemplaires')
                ->setColumns(4)
                ->setHelp('Nombre total d\'exemplaires'),
            
            // Relations
            AssociationField::new('editeur')
                ->setLabel('Éditeur')
                ->setColumns(6)
                ->setRequired(true),
            
            AssociationField::new('auteurs')
                ->setLabel('Auteur(s)')
                ->setColumns(6)
                ->setRequired(true),
            
            AssociationField::new('categories')
                ->setLabel('Catégorie(s)')
                ->setColumns(12),
        ]);
    }

    /**
     * Action personnalisée pour voir la description dans un modal
     */
    public function viewDescription(AdminContext $context)
    {
        $livre = $context->getEntity()->getInstance();
        
        return $this->render('admin/livre_description_modal.html.twig', [
            'livre' => $livre,
        ]);
    }
}

