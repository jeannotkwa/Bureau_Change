<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\AgenceRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateurs')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'app_utilisateur_index')]
    public function index(UtilisateurRepository $repo): Response
    {
        $users = $repo->findBy([], ['nom' => 'ASC']);

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $users,
        ]);
    }

    #[Route('/nouveau', name: 'app_utilisateur_new')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, AgenceRepository $agenceRepository): Response
    {
        $user = new Utilisateur();

        $form = $this->createFormBuilder($user)
            ->add('nom', TextType::class)
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('agence', EntityType::class, [
                'class' => 'App\\Entity\\Agence',
                'choices' => $agenceRepository->findBy([], ['nomAgence' => 'ASC']),
                'choice_label' => 'nomAgence',
                'placeholder' => 'Sélectionner une agence',
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashed = $hasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashed);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé');
            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvel utilisateur',
        ]);
    }

    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $user): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $user,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_utilisateur_edit')]
    public function edit(Utilisateur $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, AgenceRepository $agenceRepository): Response
    {
        $form = $this->createFormBuilder($user)
            ->add('nom', TextType::class)
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe (laisser vide pour conserver)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('agence', EntityType::class, [
                'class' => 'App\\Entity\\Agence',
                'choices' => $agenceRepository->findBy([], ['nomAgence' => 'ASC']),
                'choice_label' => 'nomAgence',
                'placeholder' => 'Sélectionner une agence',
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Mettre à jour'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $hashed = $hasher->hashPassword($user, $plain);
                $user->setPassword($hashed);
            }
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour');
            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier l\'utilisateur',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé');
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }
}
