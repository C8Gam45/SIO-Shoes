<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Entity\Product;
use App\Form\OrderFormType;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    #[Route('/order', name: 'app_order', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $cart = $session->get('cart', []);
        if (!is_array($cart) || $cart === []) {
            $this->addFlash('danger', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart');
        }

        $products = $productRepository->findBy(['id' => array_keys($cart)]);
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product->getId()] = $product;
        }

        $items = [];
        $subtotalCents = 0;
        foreach ($cart as $productId => $quantity) {
            if ((!is_int($productId) && !ctype_digit((string) $productId))
                || !is_int($quantity)
                || $quantity < 1
            ) {
                continue;
            }

            $product = $productsById[(int) $productId] ?? null;
            if (!$product instanceof Product || $product->getPrice() === null) {
                continue;
            }

            $lineCents = (int) round((float) $product->getPrice() * 100) * $quantity;
            $subtotalCents += $lineCents;
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $this->formatCents($lineCents),
            ];
        }

        if ($items === []) {
            $this->addFlash('danger', 'Aucun produit valide ne reste dans votre panier.');

            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $form = $this->createForm(OrderFormType::class, $order)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $city = $order->getCity() ?? throw new \LogicException('Une ville doit être sélectionnée pour la commande.');
            $shippingCents = (int) round((float) $city->getShippingCost() * 100);
            $order->setShippingPrice($this->formatCents($shippingCents));
            $order->setTotalPrice($this->formatCents($subtotalCents + $shippingCents));
            $order->setCreatedAt(new \DateTimeImmutable());
            foreach ($items as $item) {
                $orderProduct = (new OrderProducts())
                    ->setProduct($item['product'])
                    ->setQte($item['quantity'])
                    ->setPrice($item['product']->getPrice());
                $order->addOrderProduct($orderProduct);
                $entityManager->persist($orderProduct);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $session->remove('cart');
            $this->addFlash('success', 'Votre commande a été enregistrée.');

            return $this->redirectToRoute('order_message', [], Response::HTTP_SEE_OTHER);
        }

        $selectedCity = $form->get('city')->getData();
        $shippingCents = $selectedCity === null
            ? 0
            : (int) round((float) $selectedCity->getShippingCost() * 100);

        return $this->render('order/checkout.html.twig', [
            'form' => $form,
            'items' => $items,
            'subtotal' => $this->formatCents($subtotalCents),
            'shippingCost' => $this->formatCents($shippingCents),
            'total' => $this->formatCents($subtotalCents + $shippingCents),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/order/{id}/confirmation', name: 'app_order_confirmation', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function confirmation(Order $order, CategoryRepository $categoryRepository): Response
    {
        return $this->render('order/confirmation.html.twig', [
            'order' => $order,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/get_shipping_cost', name: 'get_shipping_cost', methods: ['POST'])]
    public function getShippingCost(Request $request, CityRepository $cityRepository): JsonResponse
    {
        $cityId = $request->request->getString('city');
        $city = ctype_digit($cityId) ? $cityRepository->find((int) $cityId) : null;
        $shippingCost = $city?->getShippingCost() ?? '10.00';

        return new JsonResponse(['shippingCost' => $shippingCost]);
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
