<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_admin_user')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user', name: 'app_user')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}/add/editor', name: 'app_user_add_editor_role')]
    public function ajouterRole(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        $roles[] = 'ROLE_EDITOR';
        $user->setRoles(array_unique($roles));
        $entityManager->flush();

        $this->addFlash('success', "Le rôle éditeur a été ajouté à l'utilisateur");

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor', name: 'app_user_remove_editor_role')]
    public function retirerRole(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = array_filter(
            $user->getRoles(),
            static fn (string $role): bool => $role !== 'ROLE_EDITOR'
        );
        $user->setRoles(array_values($roles));
        $entityManager->flush();

        $this->addFlash('danger', "Le rôle éditeur a été retiré à l'utilisateur");

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/delete', name: 'app_user_delete')]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', "L'utilisateur a été supprimé");

        return $this->redirectToRoute('app_user');
    }
}