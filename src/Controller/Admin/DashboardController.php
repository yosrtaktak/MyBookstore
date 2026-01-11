<?php

namespace App\Controller\Admin;

use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Editeur;
use App\Entity\Livre;
use App\Entity\User;
use App\Repository\CommandeRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôleur principal du tableau de bord d'administration
 * Accessible uniquement aux utilisateurs ayant le rôle ROLE_AGENT ou supérieur
 */
#[Route('/admin')]
#[IsGranted('ROLE_AGENT')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private LivreRepository $livreRepository,
        private CommandeRepository $commandeRepository,
        private UserRepository $userRepository,
        private ConfigurationRepository $configurationRepository,
    ) {
    }

    /**
     * Page d'accueil du dashboard avec les statistiques principales
     */
    #[Route('', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Get configurable stock threshold
        $stockThreshold = $this->configurationRepository->getStockAlertThreshold();

        // Récupération des statistiques pour le dashboard
        $stats = [
            'total_livres' => $this->livreRepository->count([]),
            'total_commandes' => $this->commandeRepository->count([]),
            'total_utilisateurs' => $this->userRepository->count([]),
            'commandes_en_attente' => $this->commandeRepository->count(['statut' => 'EN_ATTENTE']),
            'ca_total' => $this->commandeRepository->countTotalRevenue(),
            'livres_low_stock' => $this->livreRepository->countLowStock($stockThreshold),
            'stock_threshold' => $stockThreshold,
        ];

        $bestSellers = $this->livreRepository->findBestSellers(5);
        $recentOrders = $this->commandeRepository->findRecentOrders(5);
        $lowStockBooks = $this->livreRepository->findLowStock($stockThreshold, 5);

        // Affichage du dashboard avec les statistiques
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'bestSellers' => $bestSellers,
            'recentOrders' => $recentOrders,
            'lowStockBooks' => $lowStockBooks,
        ]);
    }

    /**
     * Configuration du dashboard
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // Titre principal de l'administration
            ->setTitle('MyBookstore - Administration');
    }

    /**
     * Configuration des assets (CSS/JS) pour toutes les pages admin
     */
    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin.css');
    }

    /**
     * Configuration du menu de navigation
     */
    public function configureMenuItems(): iterable
    {
        // Lien vers le dashboard avec icône
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Section Catalogue
        yield MenuItem::section('Catalogue');
        
        yield MenuItem::linkToCrud('Livres', 'fa fa-book', Livre::class)
            ->setBadge($this->livreRepository->count([]), 'info');
        
        yield MenuItem::linkToCrud('Auteurs', 'fa fa-user-pen', Auteur::class);
        
        yield MenuItem::linkToCrud('Éditeurs', 'fa fa-building', Editeur::class);
        
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', Categorie::class);

        // Section Ventes
        yield MenuItem::section('Ventes');
        
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Commande::class)
            ->setBadge($this->commandeRepository->count([]), 'success');

        // Section Utilisateurs (visible uniquement pour ROLE_ADMIN)
        yield MenuItem::section('👥 Utilisateurs')
            ->setPermission('ROLE_ADMIN');
        
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class)
            ->setPermission('ROLE_ADMIN')
            ->setBadge($this->userRepository->count([]), 'primary');

        // Séparateur
        yield MenuItem::section();

        // Lien pour retourner sur le site public
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left', '/');

        // Lien pour se déconnecter
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }

    /**
     * Configuration du menu utilisateur dans le header
     */
    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // Vérifier que l'utilisateur a bien une méthode getEmail()
        if (!$user instanceof \App\Entity\User) {
            return parent::configureUserMenu($user);
        }

        return parent::configureUserMenu($user)
            ->setName($user->getEmail())
            ->displayUserName(true)
            ->displayUserAvatar(false)
            ->addMenuItems([
                MenuItem::linkToUrl('Mon profil', 'fa fa-user', '/profile'),
            ]);
    }
}
