<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderFormType;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/admin/orders', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orders): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orders->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/admin/orders/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderFormType::class, $order)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shippingPrice = $order->getCity()?->getShippingCost()
                ?? throw new \LogicException('Une ville doit être sélectionnée pour la commande.');
            $order->setShippingPrice($shippingPrice);
            $order->setTotalPrice($shippingPrice);
            $order->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'La commande a été créée.');

            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/orders/{id}/edit', name: 'app_order_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderFormType::class, $order)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $previousShippingCents = (int) round((float) $order->getShippingPrice() * 100);
            $previousTotalCents = (int) round((float) $order->getTotalPrice() * 100);
            $shippingCents = (int) round((float) (
                $order->getCity()?->getShippingCost()
                ?? throw new \LogicException('Une ville doit être sélectionnée pour la commande.')
            ) * 100);
            $order->setShippingPrice(number_format($shippingCents / 100, 2, '.', ''));
            $order->setTotalPrice(number_format(($previousTotalCents - $previousShippingCents + $shippingCents) / 100, 2, '.', ''));
            $entityManager->flush();

            $this->addFlash('success', 'La commande a été modifiée.');

            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/admin/orders/{id}', name: 'app_order_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                $entityManager->remove($orderProduct);
            }
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'La commande a été supprimée.');
        } else {
            $this->addFlash('danger', 'Le jeton de sécurité est invalide.');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/order-message', name: 'order_message', methods: ['GET'])]
    public function orderMessage(CategoryRepository $categoryRepository): Response
    {
        return $this->render('order/order-message.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/editor/orders', name: 'app_orders')]
    public function getAllOrders(
        OrderRepository $orderRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $orders = $orderRepository->findAll();

        return $this->render('order/orders.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'orders' => $orders,
        ]);
    }

    #[Route('/editor/orders/{id}/delete', name: 'app_orders_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteOrderForEditor(
        Order $order,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete-editor-order-'.$order->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $this->addFlash('danger', 'Le jeton de sécurité est invalide.');

            return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
        }

        foreach ($order->getOrderProducts() as $orderProduct) {
            $entityManager->remove($orderProduct);
        }
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'La commande a été supprimée.');

        return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
    }
}