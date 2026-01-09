<?php

namespace App\Service;

use App\Entity\Livre;
use App\Repository\LivreRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion du panier d'achat
 */
class CartService
{
    private const CART_SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private LivreRepository $livreRepository
    ) {
    }

    /**
     * Récupère la session courante
     */
    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Ajoute un livre au panier ou incrémente sa quantité
     */
    public function add(int $id, int $quantity = 1): void
    {
        $cart = $this->getSession()->get(self::CART_SESSION_KEY, []);

        if (isset($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Supprime un livre du panier
     */
    public function remove(int $id): void
    {
        $cart = $this->getSession()->get(self::CART_SESSION_KEY, []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
        }

        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Met à jour la quantité d'un livre dans le panier
     */
    public function updateQuantity(int $id, int $quantity): void
    {
        $cart = $this->getSession()->get(self::CART_SESSION_KEY, []);

        if ($quantity <= 0) {
            $this->remove($id);
            return;
        }

        if (isset($cart[$id])) {
            $cart[$id] = $quantity;
        }

        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Vide complètement le panier
     */
    public function clear(): void
    {
        $this->getSession()->remove(self::CART_SESSION_KEY);
    }

    /**
     * Récupère le panier brut (tableau id => quantité)
     * 
     * @return array<int, int>
     */
    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Récupère le panier avec les données complètes des livres
     * 
     * @return array<array{livre: Livre, quantity: int}>
     */
    public function getCartWithData(): array
    {
        $cart = $this->getCart();
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $livre = $this->livreRepository->find($id);
            
            if ($livre) {
                $cartWithData[] = [
                    'livre' => $livre,
                    'quantity' => $quantity,
                ];
            }
        }

        return $cartWithData;
    }

    /**
     * Calcule le montant total du panier
     */
    public function getTotal(): float
    {
        $total = 0.0;
        $cartWithData = $this->getCartWithData();

        foreach ($cartWithData as $item) {
            $total += $item['livre']->getPrix() * $item['quantity'];
        }

        return $total;
    }

    /**
     * Retourne le nombre total d'articles dans le panier
     */
    public function getCount(): int
    {
        $cart = $this->getCart();
        return array_sum($cart);
    }

    /**
     * Vérifie si le panier est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }
}
