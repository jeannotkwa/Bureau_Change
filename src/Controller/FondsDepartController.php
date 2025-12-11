<?php

namespace App\Controller;

use App\Entity\FondsDepart;
use App\Entity\DetailsFondsDepart;
use App\Repository\FondsDepartRepository;
use App\Repository\DetailsFondsDepartRepository;
use App\Repository\AgenceRepository;
use App\Repository\DeviseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/fonds')]
#[IsGranted('ROLE_ADMIN')]
class FondsDepartController extends AbstractController
{
    #[Route('/', name: 'app_fonds_index')]
    public function index(FondsDepartRepository $repo, AgenceRepository $agenceRepository, DeviseRepository $deviseRepository, DetailsFondsDepartRepository $detailsFondsRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);
        $devises = $deviseRepository->findBy([], ['sigle' => 'ASC']);
        
        // Récupérer le rôle et l'agence de l'utilisateur
        $roles = $user->getRoles();
        $agenceId = method_exists($user, 'getAgence') && $user->getAgence() ? $user->getAgence()->getId() : null;
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        
        // Préparer les données : cumul des soldes par agence
        $agencesData = [];
        $statsGlobales = []; // Cumul total de toutes les agences par devise
        
        if ($isAdmin) {
            // Super admin : voir toutes les agences
            $agencesToShow = $agences;
        } else {
            // Utilisateur régulier : voir seulement son agence
            $agencesToShow = $agenceId ? [$agenceRepository->find($agenceId)] : [];
        }
        
        foreach ($agencesToShow as $agence) {
            $agenceIdCurrent = $agence->getId();
            
            // Récupérer les soldes actuels par devise pour cette agence
            $soldes = $detailsFondsRepo->getSoldesByAgence($agenceIdCurrent);
            
            // Préparer les montants par devise
            $deviseMontants = [];
            foreach ($soldes as $solde) {
                // $solde est maintenant un array, pas un objet
                $deviseId = $solde['id'];
                $deviseSigle = $solde['sigle'];
                $montant = (float)$solde['montant'];
                
                // Afficher tous les montants, même zéro ou négatifs
                if (!isset($deviseMontants[$deviseId])) {
                    $deviseMontants[$deviseId] = 0;
                }
                $deviseMontants[$deviseId] += $montant;
                
                // Ajouter au cumul global seulement si montant non nul
                if ($montant != 0) {
                    if (!isset($statsGlobales[$deviseSigle])) {
                        $statsGlobales[$deviseSigle] = [
                            'libelle' => $solde['libelle'],
                            'sigle' => $deviseSigle,
                            'total' => 0
                        ];
                    }
                    $statsGlobales[$deviseSigle]['total'] += $montant;
                }
            }
            
            // Récupérer l'historique des mouvements pour cette agence (pour le modal)
            $historique = $repo->createQueryBuilder('f')
                ->leftJoin('f.details', 'd')
                ->leftJoin('d.devise', 'dev')
                ->addSelect('d', 'dev')
                ->where('f.agence = :agence')
                ->setParameter('agence', $agenceIdCurrent)
                ->orderBy('f.dateJour', 'DESC')
                ->addOrderBy('f.id', 'DESC')
                ->getQuery()
                ->getResult();
            
            $agencesData[] = [
                'agence' => $agence,
                'soldes' => $deviseMontants,
                'historique' => $historique,
            ];
        }
        
        // Trier les stats globales par sigle de devise
        ksort($statsGlobales);

        return $this->render('fonds/index.html.twig', [
            'agencesData' => $agencesData,
            'devises' => $devises,
            'isAdmin' => $isAdmin,
            'statsGlobales' => $statsGlobales,
        ]);
    }

    #[Route('/nouveau', name: 'app_fonds_new')]
    public function new(Request $request, EntityManagerInterface $em, AgenceRepository $agenceRepository, DeviseRepository $deviseRepository, DetailsFondsDepartRepository $detailsFondsRepository): Response
    {
        $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);
        $devises = $deviseRepository->findBy(['statut' => 'Actif'], ['sigle' => 'ASC']);

        if ($request->isMethod('POST')) {
            try {
                $agenceId = $request->request->get('agence');
                $dateJour = $request->request->get('date');
                $devisesArray = $request->request->all('devise');
                $montantsArray = $request->request->all('montant');

                if (!$agenceId || !$dateJour) {
                    throw new \Exception('Agence et date sont obligatoires.');
                }

                $agence = $agenceRepository->find($agenceId);
                if (!$agence) {
                    throw new \Exception('Agence non trouvée.');
                }
                
                // Vérifier que la date n'est pas dans le futur
                $dateFonds = new \DateTime($dateJour);
                $today = new \DateTime();
                $today->setTime(0, 0, 0);
                $dateFonds->setTime(0, 0, 0);
                
                if ($dateFonds > $today) {
                    throw new \Exception('❌ Erreur : Impossible de créer un mouvement de fonds avec une date future. Veuillez sélectionner une date d\'aujourd\'hui ou antérieure.');
                }

                $fonds = new FondsDepart();
                $fonds->setAgence($agence);
                $fonds->setDateJour($dateFonds);
                $fonds->setStatut('ouvert');

                $em->persist($fonds);
                $em->flush();

                // Traitement des devises et montants
                foreach ($devisesArray as $index => $deviseId) {
                    $montant = $montantsArray[$index] ?? 0;
                    
                    if (!$deviseId || !$montant || $montant <= 0) {
                        continue;
                    }

                    $devise = $deviseRepository->find($deviseId);
                    if (!$devise) {
                        continue;
                    }

                    // Créer un détail de fonds de départ
                    $detailFonds = new DetailsFondsDepart();
                    $detailFonds->setFondsDepart($fonds);
                    $detailFonds->setDevise($devise);
                    $detailFonds->setMontant((string)$montant);
                    $detailFonds->setAgence($agence);

                    $em->persist($detailFonds);
                }

                $em->flush();

                // Mettre à jour les soldes des devises pour cette agence
                $this->updateAllSoldes($detailsFondsRepository, $em, $agence);

                $this->addFlash('success', '✅ Fonds de départ ajouté avec succès et soldes mis à jour !');
                return $this->redirectToRoute('app_fonds_index');

            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('fonds/form.html.twig', [
            'agences' => $agences,
            'devises' => $devises,
            'title' => 'Ajouter un fonds de départ',
        ]);
    }

    #[Route('/{id}', name: 'app_fonds_show', methods: ['GET'])]
    public function show(FondsDepart $fonds): Response
    {
        return $this->render('fonds/show.html.twig', [
            'fonds' => $fonds,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_fonds_edit')]
    public function edit(FondsDepart $fonds, Request $request, EntityManagerInterface $em, AgenceRepository $agenceRepository): Response
    {
        $form = $this->createFormBuilder($fonds)
            ->add('dateJour', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('agence', EntityType::class, [
                'class' => 'App\\Entity\\Agence',
                'choices' => $agenceRepository->findBy([], ['nomAgence' => 'ASC']),
                'choice_label' => 'nomAgence',
                'placeholder' => 'Sélectionner une agence',
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Ouvert' => 'ouvert',
                    'Clôturé' => 'cloture',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Mettre à jour'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Fonds de départ mis à jour');
            return $this->redirectToRoute('app_fonds_index');
        }

        return $this->render('fonds/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le fonds de départ',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_fonds_delete', methods: ['POST'])]
    public function delete(FondsDepart $fonds, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_fonds_' . $fonds->getId(), $request->request->get('_token'))) {
            $em->remove($fonds);
            $em->flush();
            $this->addFlash('success', 'Fonds supprimé');
        }
        return $this->redirectToRoute('app_fonds_index');
    }

    /**
     * Met à jour tous les soldes des devises pour une agence
     * en fonction des fonds disponibles (DetailsFondsDepart)
     */
    private function updateAllSoldes(DetailsFondsDepartRepository $detailsFondsRepository, EntityManagerInterface $em, $agence): void
    {
        // Récupérer tous les détails de fonds pour cette agence
        $detailsFonds = $detailsFondsRepository->findBy(['agence' => $agence]);
        
        foreach ($detailsFonds as $detail) {
            // Les soldes sont déjà mis à jour via les entités DetailsFondsDepart
            // Cette méthode peut être utilisée pour d'autres logiques de synchronisation si nécessaire
            $em->persist($detail);
        }
        
        $em->flush();
    }
}
