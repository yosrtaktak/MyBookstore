<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(LivreRepository $livreRepository): Response
    {
        // Récupérer les 8 derniers livres ajoutés
        $derniersLivres = $livreRepository->findLatestBooks(8);

        // Récupérer les livres en vedette (stock disponible)
        $livresVedette = $livreRepository->findFeaturedBooks(4);

        return $this->render('home/index.html.twig', [
            'derniersLivres' => $derniersLivres,
            'livresVedette' => $livresVedette,
        ]);
    }
}
