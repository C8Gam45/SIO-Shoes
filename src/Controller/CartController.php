<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $cart = $this->getCart($session);
        $products = $cart === [] ? [] : $productRepository->findBy(['id' => array_keys($cart)]);
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product->getId()] = $product;
        }

        $items = [];
        $totalCents = 0;
        foreach ($cart as $productId => $quantity) {
            $product = $productsById[$productId] ?? null;
            if (!$product instanceof Product) {
                continue;
            }

            $subtotalCents = $this->priceToCents($product->getPrice()) * $quantity;
            $totalCents += $subtotalCents;
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $this->formatCents($subtotalCents),
            ];
        }

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'total' => $this->formatCents($totalCents),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function addToCart(
        int $id,
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository
    ): Response {
        $product = $productRepository->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        if (!$this->isCsrfTokenValid('cart_add_'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $quantity = $request->request->getInt('quantity', 1);
        if ($quantity < 1) {
            $this->addFlash('danger', 'La quantité doit être supérieure à zéro.');

            return $this->redirectToRoute('app_home_product_show', ['id' => $id]);
        }

        $cart = $this->getCart($session);
        $cart[$id] = ($cart[$id] ?? 0) + $quantity;
        $session->set('cart', $cart);

        $this->addFlash('success', 'Le produit a été ajouté au panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function removeFromCart(int $id, Request $request, SessionInterface $session): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $cart = $this->getCart($session);
        unset($cart[$id]);
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clearCart(Request $request, SessionInterface $session): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $session->remove('cart');
        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart');
    }
    #[Route('/cart/subtract/{id}/', name: 'app_cart_subtract', methods: ['GET'])]
    public function subtractToCart(int $id, SessionInterface $session): Response
    {
        // On récupère le panier à partir de la session 'cart'
        $cart = $session->get('cart', []);

        // Si la quantité à diminuer est supérieure à 1, on décrémente.
        // Sinon, on retire le produit du panier.
        if (isset($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]); // Suppression du produit à partir de l'id
            }
        }

        // On enregistre la session
        $session->set('cart', $cart);

        // Redirection vers l'affichage du panier
        return $this->redirectToRoute('app_cart');
    }
    /**
     * @return array<int, int>
     */
    private function getCart(SessionInterface $session): array
    {
        $cart = $session->get('cart', []);
        if (!is_array($cart)) {
            return [];
        }

        $validCart = [];
        foreach ($cart as $productId => $quantity) {
            if ((is_int($productId) || ctype_digit((string) $productId))
                && is_int($quantity)
                && $quantity > 0
            ) {
                $validCart[(int) $productId] = $quantity;
            }
        }

        return $validCart;
    }

    private function priceToCents(?string $price): int
    {
        return (int) round((float) $price * 100);
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
