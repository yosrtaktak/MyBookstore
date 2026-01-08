<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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

    /**
     * Configuration des champs du formulaire d'édition des livres
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            // Informations générales
            TextField::new('titre')
                ->setLabel('Titre')
                ->setColumns(8)
                ->setRequired(true),
            
            TextField::new('isbn')
                ->setLabel('ISBN')
                ->setColumns(4)
                ->setHelp('Format: XXX-X-XXXX-XXXX-X'),
            
            // Image de couverture avec VichUploader
            TextField::new('imageFile')
                ->setFormType(VichImageType::class)
                ->setLabel('Image de couverture')
                ->setRequired(false)
                ->onlyOnForms()
                ->setHelp('Formats acceptés: JPG, PNG, WEBP (max 2MB)'),
            
            ImageField::new('imageCouverture')
                ->setBasePath('/uploads/livres')
                ->setLabel('Couverture')
                ->onlyOnIndex()
                ->setRequired(false),
            
            // Description
            TextEditorField::new('description')
                ->setLabel('Description')
                ->setRequired(false)
                ->setColumns(12),
            
            // Détails du livre
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
        ];
    }
}

