<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor/city', name: 'app_city_')]
#[IsGranted('ROLE_EDITOR')]
final class CityController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CityRepository $cityRepository): Response
    {
        return $this->render('city/index.html.twig', [
            'cities' => $cityRepository->findBy([], ['zipCode' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($city);
            $entityManager->flush();

            $this->addFlash('success', 'La ville a été ajoutée.');

            return $this->redirectToRoute('app_city_index');
        }

        return $this->render('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(City $city, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CityType::class, $city)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La ville a été modifiée.');

            return $this->redirectToRoute('app_city_index');
        }

        return $this->render('city/edit.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(City $city, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-city-'.$city->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($city->getOrders()->count() > 0) {
            $this->addFlash('danger', 'Cette ville est associée à des commandes et ne peut pas être supprimée.');

            return $this->redirectToRoute('app_city_index');
        }

        $entityManager->remove($city);
        $entityManager->flush();
        $this->addFlash('success', 'La ville a été supprimée.');

        return $this->redirectToRoute('app_city_index');
    }
}
