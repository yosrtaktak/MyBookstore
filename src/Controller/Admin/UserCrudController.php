<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    private ?string $generatedPassword = null;

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['email', 'nom', 'prenom', 'telephone'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', '👥 Gestion des utilisateurs')
            ->setPageTitle('new', '➕ Créer un nouvel utilisateur (Agent ou Admin)')
            ->setPageTitle('edit', 'Modifier %entity_as_string%')
            ->setPageTitle('detail', 'Détails de %entity_as_string%')
            ->setHelp('new', 'Créez un compte Agent ou Administrateur. Un mot de passe sera généré automatiquement si vous n\'en spécifiez pas.');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée pour réinitialiser le mot de passe
        $resetPassword = Action::new('resetPassword', 'Réinitialiser', 'fa fa-key')
            ->linkToCrudAction('resetPassword')
            ->setCssClass('btn btn-warning btn-sm')
            ->displayIf(fn ($entity) => $entity instanceof User);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resetPassword)
            ->add(Crud::PAGE_DETAIL, $resetPassword)
            ->add(Crud::PAGE_EDIT, $resetPassword)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('nom', 'Nom'))
            ->add(TextFilter::new('prenom', 'Prénom'));
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')
                ->hideOnForm(),
            EmailField::new('email')
                ->setLabel('Email')
                ->setHelp('Adresse email unique de l\'utilisateur (servira d\'identifiant de connexion)')
                ->setRequired(true)
                ->setColumns(12),
            TextField::new('nom')
                ->setLabel('Nom')
                ->setRequired(true)
                ->setColumns(6),
            TextField::new('prenom')
                ->setLabel('Prénom')
                ->setRequired(true)
                ->setColumns(6),
            ChoiceField::new('roles')
                ->setLabel('Rôle')
                ->setChoices(
                    $pageName === Crud::PAGE_NEW
                        ? [
                            'Agent (Accès back-office)' => 'ROLE_AGENT',
                            'Administrateur (Tous droits)' => 'ROLE_ADMIN',
                        ]
                        : [
                            'Abonné (Accès client uniquement)' => 'ROLE_ABONNE',
                            'Agent (Accès back-office)' => 'ROLE_AGENT',
                            'Administrateur (Tous droits)' => 'ROLE_ADMIN',
                        ]
                )
                ->allowMultipleChoices(true)
                ->renderExpanded(true)
                ->setRequired(true)
                ->setHelp(
                    $pageName === Crud::PAGE_NEW
                        ? '⚠️ Agent : peut gérer le catalogue et les commandes | Admin : peut tout gérer + créer des utilisateurs'
                        : '💡 Vous pouvez promouvoir un Abonné en Agent ou Admin'
                )
                ->setColumns(12)
                ->formatValue(function ($value, $entity) {
                    $roles = $entity->getRoles();
                    if (in_array('ROLE_ADMIN', $roles)) return '🔴 Administrateur';
                    if (in_array('ROLE_AGENT', $roles)) return '🟠 Agent';
                    return '🟢 Abonné';
                }),
            TelephoneField::new('telephone')
                ->setLabel('Téléphone')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('adresse')
                ->setLabel('Adresse')
                ->setRequired(false)
                ->setColumns(8)
                ->hideOnIndex(),
            TextField::new('ville')
                ->setLabel('Ville')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('codePostal')
                ->setLabel('Code postal')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),
        ];

        // Afficher le champ de mot de passe uniquement sur la page NEW
        if (Crud::PAGE_NEW === $pageName) {
            $fields[] = TextField::new('plainPassword')
                ->setLabel('Mot de passe (optionnel)')
                ->setFormType(PasswordType::class)
                ->setHelp('⚠️ Laissez vide pour générer automatiquement un mot de passe sécurisé. Le mot de passe sera affiché après la création.')
                ->setRequired(false)
                ->setColumns(12);
        }

        // Afficher le champ de mot de passe sur la page EDIT pour permettre la modification
        if (Crud::PAGE_EDIT === $pageName) {
            $fields[] = TextField::new('plainPassword')
                ->setLabel('Nouveau mot de passe (optionnel)')
                ->setFormType(PasswordType::class)
                ->setHelp('💡 Laissez vide pour conserver le mot de passe actuel. Si vous remplissez ce champ, le mot de passe sera modifié.')
                ->setRequired(false)
                ->setColumns(12);
        }

        // Cacher les commandes sur le formulaire, afficher sur index/detail
        if (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            $fields[] = AssociationField::new('commandes')
                ->setLabel('Commandes')
                ->formatValue(function ($value, $entity) {
                    return count($entity->getCommandes()) . ' commande(s)';
                });
        }

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        // Récupérer le mot de passe du formulaire
        $plainPassword = $entityInstance->plainPassword ?? null;

        // Si pas de mot de passe fourni, générer un mot de passe temporaire
        if (empty($plainPassword)) {
            $this->generatedPassword = $this->generateTemporaryPassword();
            $plainPassword = $this->generatedPassword;
        } else {
            // L'admin a saisi un mot de passe manuellement
            $this->generatedPassword = $plainPassword;
        }

        // Hash le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
        $entityInstance->setPassword($hashedPassword);

        // S'assurer qu'un rôle valide est défini (AGENT ou ADMIN uniquement pour création admin)
        $roles = $entityInstance->getRoles();
        if (empty($roles) || (!in_array('ROLE_AGENT', $roles) && !in_array('ROLE_ADMIN', $roles))) {
            // Par défaut ROLE_AGENT si pas de rôle valide
            $entityInstance->setRoles(['ROLE_AGENT']);
        }

        parent::persistEntity($entityManager, $entityInstance);

        // Afficher le mot de passe généré ou saisi
        if ($this->generatedPassword) {
            $this->addFlash('success', sprintf(
                '<div style="max-width:600px;margin:20px auto;padding:30px;background:linear-gradient(135deg,#667eea 0%%,#764ba2 100%%);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);color:#fff;font-family:system-ui,-apple-system,sans-serif;">'
                . '<h3 style="margin:0 0 25px 0;font-size:1.5em;font-weight:600;text-align:center;">Utilisateur créé avec succès</h3>'
                . '<div style="background:rgba(255,255,255,0.95);padding:25px;border-radius:8px;color:#2d3748;margin-bottom:20px;">'
                . '<div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #e2e8f0;">'
                . '<p style="margin:0 0 8px 0;font-size:0.85em;color:#718096;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Email</p>'
                . '<p style="margin:0;font-size:1.15em;color:#1a202c;font-weight:600;">%s</p>'
                . '</div>'
                . '<div>'
                . '<p style="margin:0 0 8px 0;font-size:0.85em;color:#718096;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Mot de passe temporaire</p>'
                . '<div style="background:#f7fafc;border:2px solid #e53e3e;border-radius:6px;padding:15px;text-align:center;">'
                . '<code style="font-family:\'Courier New\',monospace;font-size:1.3em;color:#e53e3e;font-weight:700;letter-spacing:1px;">%s</code>'
                . '</div>'
                . '</div>'
                . '</div>'
                . '<div style="background:rgba(255,255,255,0.1);padding:15px;border-radius:6px;border-left:4px solid #fbbf24;">'
                . '<p style="margin:0;font-size:0.9em;line-height:1.6;"><strong>Important :</strong> Veuillez noter ce mot de passe et le communiquer de manière sécurisée à l\'utilisateur. Cette information ne sera plus affichée.</p>'
                . '</div>'
                . '</div>',
                $entityInstance->getEmail(),
                $this->generatedPassword
            ));
            
            // Reset pour la prochaine création
            $this->generatedPassword = null;
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        // Récupérer le mot de passe du formulaire (si fourni)
        $plainPassword = $entityInstance->plainPassword ?? null;

        // Si un nouveau mot de passe est fourni, le hasher
        if (!empty($plainPassword)) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
            $entityInstance->setPassword($hashedPassword);
            
            $this->addFlash('success', sprintf(
                '✅ Utilisateur <strong>%s</strong> modifié avec succès. Le mot de passe a été mis à jour.',
                $entityInstance->getEmail()
            ));
        } else {
            // Pas de nouveau mot de passe, on garde l'ancien (ne rien faire)
            $this->addFlash('success', sprintf(
                '✅ Utilisateur <strong>%s</strong> modifié avec succès.',
                $entityInstance->getEmail()
            ));
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Action personnalisée pour réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();
        
        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur invalide.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Générer un nouveau mot de passe
        $newPassword = $this->generateTemporaryPassword();
        
        // Hash et mise à jour
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->flush();

        // Afficher le nouveau mot de passe
        $this->addFlash('success', sprintf(
            '<div style="max-width:600px;margin:20px auto;padding:30px;background:linear-gradient(135deg,#f093fb 0%%,#f5576c 100%%);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);color:#fff;font-family:system-ui,-apple-system,sans-serif;">'
            . '<h3 style="margin:0 0 25px 0;font-size:1.5em;font-weight:600;text-align:center;">Mot de passe réinitialisé</h3>'
            . '<div style="background:rgba(255,255,255,0.95);padding:25px;border-radius:8px;color:#2d3748;margin-bottom:20px;">'
            . '<div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #e2e8f0;">'
            . '<p style="margin:0 0 8px 0;font-size:0.85em;color:#718096;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Utilisateur</p>'
            . '<p style="margin:0;font-size:1.15em;color:#1a202c;font-weight:600;">%s</p>'
            . '</div>'
            . '<div>'
            . '<p style="margin:0 0 8px 0;font-size:0.85em;color:#718096;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Nouveau mot de passe</p>'
            . '<div style="background:#f7fafc;border:2px solid #e53e3e;border-radius:6px;padding:15px;text-align:center;">'
            . '<code style="font-family:\'Courier New\',monospace;font-size:1.3em;color:#e53e3e;font-weight:700;letter-spacing:1px;">%s</code>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div style="background:rgba(255,255,255,0.1);padding:15px;border-radius:6px;border-left:4px solid #fbbf24;">'
            . '<p style="margin:0;font-size:0.9em;line-height:1.6;"><strong>Important :</strong> Communiquez ce nouveau mot de passe à l\'utilisateur de manière sécurisée. L\'ancien mot de passe ne fonctionnera plus.</p>'
            . '</div>'
            . '</div>',
            $user->getEmail(),
            $newPassword
        ));

        // Rediriger vers la page de liste des utilisateurs
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        
        return $this->redirect($url);
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}
