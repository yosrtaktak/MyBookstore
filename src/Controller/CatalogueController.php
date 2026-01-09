<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    #[Route('/catalogue', name: 'app_catalogue')]
    public function index(
        Request $request,
        LivreRepository $livreRepository,
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository
    ): Response {
        // Récupérer les paramètres de filtrage et tri
        $categorieId = $request->query->getInt('categorie', 0) ?: null;
        $editeurId = $request->query->getInt('editeur', 0) ?: null;
        $prixMin = $request->query->get('prix_min') ? (float) $request->query->get('prix_min') : null;
        $prixMax = $request->query->get('prix_max') ? (float) $request->query->get('prix_max') : null;
        $search = $request->query->get('search', '');
        $orderBy = $request->query->get('tri', 'newest'); // newest, price_asc, price_desc, title
        $page = max(1, $request->query->getInt('page', 1));

        // Récupérer les livres avec filtres et pagination
        $result = $livreRepository->findWithFiltersAndPagination(
            $categorieId,
            $editeurId,
            $prixMin,
            $prixMax,
            $search ?: null,
            $orderBy,
            $page,
            12 // 12 livres par page
        );

        // Récupérer toutes les catégories et éditeurs pour les filtres
        $categories = $categorieRepository->findAll();
        $editeurs = $editeurRepository->findAll();

        // Récupérer les entités sélectionnées pour affichage
        $categorieSelectionnee = $categorieId ? $categorieRepository->find($categorieId) : null;
        $editeurSelectionne = $editeurId ? $editeurRepository->find($editeurId) : null;

        return $this->render('catalogue/index.html.twig', [
            'livres' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'currentPage' => $result['currentPage'],
            'categories' => $categories,
            'editeurs' => $editeurs,
            'filtres' => [
                'categorie' => $categorieId,
                'editeur' => $editeurId,
                'prix_min' => $prixMin,
                'prix_max' => $prixMax,
                'search' => $search,
                'tri' => $orderBy,
            ],
            'categorieSelectionnee' => $categorieSelectionnee,
            'editeurSelectionne' => $editeurSelectionne,
        ]);
    }

    #[Route('/catalogue/livre/{id}', name: 'app_book_show', requirements: ['id' => '\d+'])]
    public function show(int $id, LivreRepository $livreRepository): Response
    {
        $livre = $livreRepository->find($id);

        if (!$livre) {
            throw $this->createNotFoundException('Le livre demandé n\'existe pas.');
        }

        // Récupérer les livres similaires (même catégorie)
        $livresSimilaires = $livreRepository->findSimilarBooks($livre, 4);

        return $this->render('book/show.html.twig', [
            'livre' => $livre,
            'livresSimilaires' => $livresSimilaires,
        ]);
    }
}
