<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Security\PasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use App\Form\ProfileFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/private')]
final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profil/modifier', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        // toujours l'utilisateur connecté : aucun id dans l'URL (protection IDOR)
        $form = $this->createForm(ProfileFormType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a bien été modifié.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', ['form' => $form]);
    }

    #[Route('/mot-de-passe', name: 'app_change_password')]
    public function changePassword(Request $request, PasswordManager $passwordManager, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $newPassword */
            $newPassword = $form->get('newPassword')->getData();

            if ($passwordManager->isAlreadyUsed($user, $newPassword)) {
                $form->get('newPassword')->get('first')->addError(
                    new FormError('Vous avez déjà utilisé ce mot de passe. Choisissez-en un nouveau.')
                );
            } else {
                $passwordManager->setPassword($user, $newPassword);
                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a bien été modifié.');

                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}
