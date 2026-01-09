<?php

namespace App\Controller;

use App\Service\CartService;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
#[IsGranted('ROLE_ABONNE')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private LivreRepository $livreRepository
    ) {
    }

    /**
     * Affiche le panier
     */
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(): Response
    {
        $cartItems = $this->cartService->getCartWithData();
        $total = $this->cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Ajoute un livre au panier
     */
    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        $livre = $this->livreRepository->find($id);

        if (!$livre) {
            $this->addFlash('error', 'Le livre demandé n\'existe pas.');
            return $this->redirectToRoute('app_catalogue');
        }

        if ($livre->getStock() <= 0) {
            $this->addFlash('error', 'Ce livre n\'est plus en stock.');
            return $this->redirectToRoute('app_book_show', ['id' => $id]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        // Vérifier que la quantité demandée ne dépasse pas le stock
        $currentCart = $this->cartService->getCart();
        $currentQuantity = $currentCart[$id] ?? 0;
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > $livre->getStock()) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant. Disponible : %d, dans le panier : %d.',
                $livre->getStock(),
                $currentQuantity
            ));
            return $this->redirectToRoute('app_book_show', ['id' => $id]);
        }

        $this->cartService->add($id, $quantity);
        
        $this->addFlash('success', sprintf(
            '"%s" a été ajouté au panier (quantité : %d).',
            $livre->getTitre(),
            $quantity
        ));

        return $this->redirectToRoute('app_cart');
    }

    /**
     * Modifie la quantité d'un livre dans le panier
     */
    #[Route('/modifier/{id}/{quantity}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, int $quantity): Response
    {
        $livre = $this->livreRepository->find($id);

        if (!$livre) {
            $this->addFlash('error', 'Le livre demandé n\'existe pas.');
            return $this->redirectToRoute('app_cart');
        }

        if ($quantity <= 0) {
            return $this->remove($id);
        }

        if ($quantity > $livre->getStock()) {
            $this->addFlash('error', sprintf(
                'Stock insuffisant pour "%s". Disponible : %d.',
                $livre->getTitre(),
                $livre->getStock()
            ));
            return $this->redirectToRoute('app_cart');
        }

        $this->cartService->updateQuantity($id, $quantity);
        
        $this->addFlash('success', 'Quantité mise à jour.');

        return $this->redirectToRoute('app_cart');
    }

    /**
     * Supprime un livre du panier
     */
    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $livre = $this->livreRepository->find($id);
        
        $this->cartService->remove($id);
        
        if ($livre) {
            $this->addFlash('success', sprintf(
                '"%s" a été retiré du panier.',
                $livre->getTitre()
            ));
        }

        return $this->redirectToRoute('app_cart');
    }

    /**
     * Vide complètement le panier
     */
    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        
        $this->addFlash('success', 'Votre panier a été vidé.');

        return $this->redirectToRoute('app_cart');
    }

    /**
     * Valide la commande et crée une commande en base de données
     */
    #[Route('/valider', name: 'app_panier_valider', methods: ['GET', 'POST'])]
    public function validate(EntityManagerInterface $em): Response
    {
        if ($this->cartService->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $cartItems = $this->cartService->getCartWithData();

        // Vérifier la disponibilité des stocks
        foreach ($cartItems as $item) {
            $livre = $item['livre'];
            $quantity = $item['quantity'];
            
            if ($livre->getStock() < $quantity) {
                $this->addFlash('error', sprintf(
                    'Le livre "%s" n\'est plus disponible en quantité suffisante (stock: %d).',
                    $livre->getTitre(),
                    $livre->getStock()
                ));
                return $this->redirectToRoute('app_cart');
            }
        }

        // Créer la commande
        $commande = new \App\Entity\Commande();
        $commande->setUser($user);
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut('En attente');
        $commande->setAdresseLivraison($user->getAdresse());
        $commande->setVilleLivraison($user->getVille());
        $commande->setCodePostalLivraison($user->getCodePostal());

        $montantTotal = 0;

        // Créer les lignes de commande
        foreach ($cartItems as $item) {
            $livre = $item['livre'];
            $quantity = $item['quantity'];

            $ligneCommande = new \App\Entity\LigneCommande();
            $ligneCommande->setCommande($commande);
            $ligneCommande->setLivre($livre);
            $ligneCommande->setQuantite($quantity);
            $ligneCommande->setPrixUnitaire($livre->getPrix());

            $montantTotal += $livre->getPrix() * $quantity;

            // Déduire du stock
            $livre->setStock($livre->getStock() - $quantity);

            $em->persist($ligneCommande);
        }

        $commande->setMontantTotal($montantTotal);
        $em->persist($commande);
        $em->flush();

        // Vider le panier
        $this->cartService->clear();

        $this->addFlash('success', sprintf(
            'Votre commande n°%d a été enregistrée avec succès ! Montant total : %.2f €',
            $commande->getId(),
            $montantTotal
        ));

        return $this->redirectToRoute('app_profile');
    }
}
