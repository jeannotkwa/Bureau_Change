<?php

namespace App\Controller;

use App\Entity\Agence;
use App\Repository\AgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agences')]
class AgenceController extends AbstractController
{
    #[Route('/', name: 'app_agence_index')]
    public function index(AgenceRepository $agenceRepository): Response
    {
        $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);

        return $this->render('agence/index.html.twig', [
            'agences' => $agences,
        ]);
    }

    #[Route('/nouvelle', name: 'app_agence_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $agence = new Agence();

        $form = $this->createFormBuilder($agence)
            ->add('nomAgence', TextType::class)
            ->add('adresse', TextType::class, ['required' => false])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('email', TextType::class, ['required' => false])
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
            $em->persist($agence);
            $em->flush();
            $this->addFlash('success', 'Agence créée');
            return $this->redirectToRoute('app_agence_index');
        }

        return $this->render('agence/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvelle agence',
        ]);
    }

    #[Route('/{id}', name: 'app_agence_show', methods: ['GET'])]
    public function show(Agence $agence): Response
    {
        return $this->render('agence/show.html.twig', [
            'agence' => $agence,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_agence_edit')]
    public function edit(Agence $agence, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($agence)
            ->add('nomAgence', TextType::class)
            ->add('adresse', TextType::class, ['required' => false])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('email', TextType::class, ['required' => false])
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
            $em->flush();
            $this->addFlash('success', 'Agence mise à jour');
            return $this->redirectToRoute('app_agence_index');
        }

        return $this->render('agence/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier l\'agence',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_agence_delete', methods: ['POST'])]
    public function delete(Agence $agence, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_agence_' . $agence->getId(), $request->request->get('_token'))) {
            $em->remove($agence);
            $em->flush();
            $this->addFlash('success', 'Agence supprimée');
        }

        return $this->redirectToRoute('app_agence_index');
    }
}
