<?php

namespace App\Controller;

use App\Entity\Devise;
use App\Repository\DeviseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/devises')]
class DeviseController extends AbstractController
{
    #[Route('/', name: 'app_devise_index')]
    public function index(DeviseRepository $deviseRepository): Response
    {
        $devises = $deviseRepository->findBy([], ['sigle' => 'ASC']);

        return $this->render('devise/index.html.twig', [
            'devises' => $devises,
        ]);
    }

    #[Route('/nouvelle', name: 'app_devise_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $devise = new Devise();
        $devise->setStatut('Actif');

        // Handle POST request
        if ($request->isMethod('POST')) {
            $errors = [];

            $sigle = $request->request->get('sigle');
            $libelle = $request->request->get('libelle');
            $tauxAchat = floatval($request->request->get('taux_achat') ?? 0);
            $tauxVente = floatval($request->request->get('taux_vente') ?? 0);

            // Validation
            if (!$sigle) $errors[] = "Le sigle est requis.";
            if (!$libelle) $errors[] = "L'intitulé est requis.";
            if ($tauxAchat <= 0) $errors[] = "Le taux d'achat doit être positif.";
            if ($tauxVente <= 0) $errors[] = "Le taux de vente doit être positif.";

            if ($errors) {
                return $this->render('devise/form.html.twig', [
                    'errors' => $errors,
                    'form_data' => [
                        'sigle' => $sigle,
                        'libelle' => $libelle,
                        'taux_achat' => $tauxAchat,
                        'taux_vente' => $tauxVente,
                    ],
                    'title' => 'Nouvelle devise',
                    'isEditing' => false,
                    'devise' => null,
                ]);
            }

            try {
                $devise->setSigle(strtoupper($sigle));
                $devise->setLibelle($libelle);
                $devise->setTauxAchat($tauxAchat);
                $devise->setTauxVente($tauxVente);
                $devise->setStatut('Actif');

                $em->persist($devise);
                $em->flush();

                $this->addFlash('success', '✅ Devise créée avec succès.');
                return $this->redirectToRoute('app_devise_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', '❌ Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('devise/form.html.twig', [
            'errors' => [],
            'form_data' => [],
            'title' => 'Nouvelle devise',
            'isEditing' => false,
            'devise' => null,
        ]);
    }

    #[Route('/{id}', name: 'app_devise_show')]
    public function show(Devise $devise): Response
    {
        return $this->render('devise/show.html.twig', [
            'devise' => $devise,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_devise_edit')]
    public function edit(Devise $devise, Request $request, EntityManagerInterface $em): Response
    {
        // Handle POST request
        if ($request->isMethod('POST')) {
            $errors = [];

            $sigle = $request->request->get('sigle');
            $libelle = $request->request->get('libelle');
            $tauxAchat = floatval($request->request->get('taux_achat') ?? 0);
            $tauxVente = floatval($request->request->get('taux_vente') ?? 0);

            // Validation
            if (!$sigle) $errors[] = "Le sigle est requis.";
            if (!$libelle) $errors[] = "L'intitulé est requis.";
            if ($tauxAchat <= 0) $errors[] = "Le taux d'achat doit être positif.";
            if ($tauxVente <= 0) $errors[] = "Le taux de vente doit être positif.";

            if ($errors) {
                return $this->render('devise/form.html.twig', [
                    'errors' => $errors,
                    'form_data' => [
                        'sigle' => $sigle,
                        'libelle' => $libelle,
                        'taux_achat' => $tauxAchat,
                        'taux_vente' => $tauxVente,
                    ],
                    'title' => 'Modifier la devise',
                    'isEditing' => true,
                    'devise' => $devise,
                ]);
            }

            try {
                $devise->setSigle(strtoupper($sigle));
                $devise->setLibelle($libelle);
                $devise->setTauxAchat($tauxAchat);
                $devise->setTauxVente($tauxVente);

                $em->flush();

                $this->addFlash('success', '✅ Devise modifiée avec succès.');
                return $this->redirectToRoute('app_devise_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', '❌ Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('devise/form.html.twig', [
            'errors' => [],
            'form_data' => [
                'sigle' => $devise->getSigle(),
                'libelle' => $devise->getLibelle(),
                'taux_achat' => $devise->getTauxAchat(),
                'taux_vente' => $devise->getTauxVente(),
            ],
            'title' => 'Modifier la devise',
            'isEditing' => true,
            'devise' => $devise,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_devise_delete', methods: ['POST'])]
    public function delete(Devise $devise, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_devise_' . $devise->getId(), $request->request->get('_token'))) {
            $em->remove($devise);
            $em->flush();
            $this->addFlash('success', 'Devise supprimée');
        }

        return $this->redirectToRoute('app_devise_index');
    }
}
