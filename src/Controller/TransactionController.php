<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\DetailsTransaction;
use App\Entity\TypeIdentite;
use App\Repository\TransactionRepository;
use App\Repository\DeviseRepository;
use App\Repository\AgenceRepository;
use App\Repository\DetailsFondsDepartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/transactions')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'app_transaction_index')]
    public function index(Request $request, TransactionRepository $repository, AgenceRepository $agenceRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le rôle et l'agence de l'utilisateur
        $roles = $user->getRoles();
        $agenceId = method_exists($user, 'getAgence') && $user->getAgence() ? $user->getAgence()->getId() : null;
        
        // Filtrer selon le rôle
        $queryBuilder = $repository->createQueryBuilder('t')
            ->leftJoin('t.details', 'td')
            ->leftJoin('t.utilisateur', 'u')
            ->leftJoin('t.agence', 'a')
            ->addSelect('td', 'u', 'a')
            ->orderBy('t.dateTransaction', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        // Filtrage par rôle
        $filterAgence = $request->query->get('id_agence');
        
        if (in_array('ROLE_ADMIN', $roles)) {
            // Super admin : voir toutes les transactions avec filtre optionnel
            if ($filterAgence) {
                $queryBuilder->andWhere('t.agence = :agence')
                    ->setParameter('agence', $filterAgence);
            }
        } else {
            // Utilisateurs réguliers et superviseurs : voir seulement leur agence
            if ($agenceId) {
                $queryBuilder->andWhere('t.agence = :agence')
                    ->setParameter('agence', $agenceId);
            }
        }

        $transactions = $queryBuilder
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        // Charger toutes les agences pour le filtre admin
        $agences = [];
        if (in_array('ROLE_ADMIN', $roles)) {
            $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);
        }

        return $this->render('transaction/index.html.twig', [
            'transactions' => $transactions,
            'agences' => $agences,
            'roles' => $roles,
            'filterAgence' => $filterAgence,
            'isAdmin' => in_array('ROLE_ADMIN', $roles),
        ]);
    }

    #[Route('/nouvelle', name: 'app_transaction_new')]
    public function new(Request $request, EntityManagerInterface $em, DeviseRepository $deviseRepository, DetailsFondsDepartRepository $detailsFondsRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $transaction = new Transaction();
        $transaction->setReference('TX-' . strtoupper(uniqid()));
        $transaction->setUtilisateur($user);
        if (method_exists($user, 'getAgence')) {
            $transaction->setAgence($user->getAgence());
        }

        // Charger les devises pour les listes déroulantes
        $devises = $deviseRepository->findBy(['statut' => 'Actif'], ['sigle' => 'ASC']);

        $form = $this->createFormBuilder($transaction)
            ->add('nom', TextType::class)
            ->add('identite', EntityType::class, [
                'class' => TypeIdentite::class,
                'choice_label' => 'libelleIdentite',
            ])
            ->add('adresse', TextType::class)
            ->add('telephone', TextType::class)
            ->add('natureOperation', ChoiceType::class, [
                'choices' => [
                    'Achat' => 'achat',
                    'Vente' => 'vente',
                ],
            ])
            ->add('dateTransaction', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['max' => (new \DateTime())->format('Y-m-d')],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer la transaction'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données des détails
            $deviseRemiseId = $request->request->get('devise_remise');
            $deviseSolliciteeId = $request->request->get('devise_sollicitee');
            $montant = $request->request->get('montant');
            $taux = $request->request->get('taux');
            
            // Valider les données
            if (!$deviseRemiseId || !$deviseSolliciteeId || !$montant || !$taux) {
                $this->addFlash('error', 'Tous les champs des détails sont obligatoires');
                // Conserver les données du formulaire
                return $this->render('transaction/form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Nouvelle transaction',
                    'devises' => $devises,
                    'formData' => [
                        'devise_remise' => $deviseRemiseId,
                        'devise_sollicitee' => $deviseSolliciteeId,
                        'montant' => $montant,
                        'taux' => $taux,
                    ]
                ]);
            }
            
            // Vérifier que la date n'est pas dans le futur
            $dateTransaction = $transaction->getDateTransaction();
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            
            if ($dateTransaction > $today) {
                $this->addFlash('error', '❌ Erreur : Impossible de créer une transaction avec une date future. Veuillez sélectionner une date d\'aujourd\'hui ou antérieure.');
                return $this->render('transaction/form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Nouvelle transaction',
                    'devises' => $devises,
                    'formData' => [
                        'devise_remise' => $deviseRemiseId,
                        'devise_sollicitee' => $deviseSolliciteeId,
                        'montant' => $montant,
                        'taux' => $taux,
                    ]
                ]);
            }
            
            // Créer le détail de la transaction
            $deviseRemise = $deviseRepository->find($deviseRemiseId);
            $deviseSollicitee = $deviseRepository->find($deviseSolliciteeId);
            
            if (!$deviseRemise || !$deviseSollicitee) {
                $this->addFlash('error', 'Devises non trouvées');
                return $this->render('transaction/form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Nouvelle transaction',
                    'devises' => $devises,
                    'formData' => [
                        'devise_remise' => $deviseRemiseId,
                        'devise_sollicitee' => $deviseSolliciteeId,
                        'montant' => $montant,
                        'taux' => $taux,
                    ]
                ]);
            }
            
            // Calculer la contre-valeur
            $contreValeur = (float)$montant * (float)$taux;
            $natureOp = $transaction->getNatureOperation();
            
            // Vérifier l'agence
            $agence = $transaction->getAgence();
            if (!$agence) {
                $this->addFlash('error', 'Agence non trouvée');
                return $this->render('transaction/form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Nouvelle transaction',
                    'devises' => $devises,
                    'formData' => [
                        'devise_remise' => $deviseRemiseId,
                        'devise_sollicitee' => $deviseSolliciteeId,
                        'montant' => $montant,
                        'taux' => $taux,
                    ]
                ]);
            }
            
            // Logique selon le type d'opération
            if ($natureOp === 'achat') {
                // ACHAT : Vérifier si Montant <= Solde de la devise sollicitée
                $soldeDeviceSollicitee = $this->getSoldeAgence($detailsFondsRepository, $agence, $deviseSollicitee);
                
                if ($soldeDeviceSollicitee < (float)$montant) {
                    $this->addFlash('error', sprintf(
                        'ACHAT : Solde insuffisant pour %s. Solde disponible: %s, Montant demandé: %s',
                        $deviseSollicitee->getSigle(),
                        number_format($soldeDeviceSollicitee, 2, ',', ' '),
                        number_format((float)$montant, 2, ',', ' ')
                    ));
                    return $this->render('transaction/form.html.twig', [
                        'form' => $form->createView(),
                        'title' => 'Nouvelle transaction',
                        'devises' => $devises,
                        'formData' => [
                            'devise_remise' => $deviseRemiseId,
                            'devise_sollicitee' => $deviseSolliciteeId,
                            'montant' => $montant,
                            'taux' => $taux,
                        ],
                        'focusField' => 'montant'
                    ]);
                }
            } elseif ($natureOp === 'vente') {
                // VENTE : Vérifier si Contre-valeur <= Solde de la devise sollicitée
                $soldeDeviceSollicitee = $this->getSoldeAgence($detailsFondsRepository, $agence, $deviseSollicitee);
                
                if ($soldeDeviceSollicitee < $contreValeur) {
                    $this->addFlash('error', sprintf(
                        'VENTE : Solde insuffisant pour %s. Solde disponible: %s, Contre-valeur demandée: %s',
                        $deviseSollicitee->getSigle(),
                        number_format($soldeDeviceSollicitee, 2, ',', ' '),
                        number_format($contreValeur, 2, ',', ' ')
                    ));
                    return $this->render('transaction/form.html.twig', [
                        'form' => $form->createView(),
                        'title' => 'Nouvelle transaction',
                        'devises' => $devises,
                        'formData' => [
                            'devise_remise' => $deviseRemiseId,
                            'devise_sollicitee' => $deviseSolliciteeId,
                            'montant' => $montant,
                            'taux' => $taux,
                        ],
                        'focusField' => 'montant'
                    ]);
                }
            }
            
            // Enregistrer la transaction et le détail
            $em->persist($transaction);
            $em->flush();
            
            // Créer le détail de transaction
            $detail = new DetailsTransaction();
            $detail->setTransaction($transaction);
            $detail->setDeviseInput($deviseRemise);
            $detail->setDeviseOutput($deviseSollicitee);
            $detail->setMontant((string)$montant);
            $detail->setTaux((string)$taux);
            $detail->setContreValeur((string)$contreValeur);
            
            $em->persist($detail);
            $em->flush();
            
            // Mettre à jour les soldes des fonds
            if ($natureOp === 'achat') {
                // Augmenter le solde de la devise remise du contre-valeur
                $this->updateSoldeAgence($detailsFondsRepository, $em, $agence, $deviseRemise, $contreValeur);
                // Diminuer le solde de la devise sollicitée du montant
                $this->updateSoldeAgence($detailsFondsRepository, $em, $agence, $deviseSollicitee, -(float)$montant);
            } elseif ($natureOp === 'vente') {
                // Augmenter le solde de la devise remise du montant
                $this->updateSoldeAgence($detailsFondsRepository, $em, $agence, $deviseRemise, (float)$montant);
                // Diminuer le solde de la devise sollicitée de la contre-valeur
                $this->updateSoldeAgence($detailsFondsRepository, $em, $agence, $deviseSollicitee, -$contreValeur);
            }
            
            $this->addFlash('success', 'Transaction enregistrée et soldes mis à jour');
            return $this->redirectToRoute('app_transaction_index');
        }

        return $this->render('transaction/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvelle transaction',
            'devises' => $devises,
        ]);
    }

    #[Route('/{id}', name: 'app_transaction_show', methods: ['GET'])]
    public function show(Transaction $transaction): Response
    {
        return $this->render('transaction/show.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    #[Route('/{id}/imprimer', name: 'app_transaction_print', methods: ['GET'])]
    public function print(Transaction $transaction): Response
    {
        return $this->render('transaction/print.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Transaction $transaction, Request $request, EntityManagerInterface $em, DeviseRepository $deviseRepository): Response
    {
        // Charger les devises pour les listes déroulantes
        $devises = $deviseRepository->findBy(['statut' => 'Actif'], ['sigle' => 'ASC']);
        
        $form = $this->createFormBuilder($transaction)
            ->add('nom', TextType::class)
            ->add('identite', EntityType::class, [
                'class' => TypeIdentite::class,
                'choice_label' => 'libelleIdentite',
            ])
            ->add('adresse', TextType::class)
            ->add('telephone', TextType::class)
            ->add('natureOperation', ChoiceType::class, [
                'choices' => [
                    'Achat' => 'achat',
                    'Vente' => 'vente',
                ],
            ])
            ->add('dateTransaction', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['max' => (new \DateTime())->format('Y-m-d')],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Transaction modifiée');
            return $this->redirectToRoute('app_transaction_index');
        }

        return $this->render('transaction/form.html.twig', [
            'form' => $form->createView(),
            'transaction' => $transaction,
            'title' => 'Modifier la transaction',
            'devises' => $devises,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_transaction_delete', methods: ['POST'])]
    public function delete(Transaction $transaction, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_transaction_' . $transaction->getId(), $request->request->get('_token'))) {
            $em->remove($transaction);
            $em->flush();
            $this->addFlash('success', 'Transaction supprimée');
        }

        return $this->redirectToRoute('app_transaction_index');
    }

    /**
     * Récupère le solde total d'une agence pour une devise donnée
     */
    private function getSoldeAgence(DetailsFondsDepartRepository $detailsFondsRepository, $agence, $devise): float
    {
        $soldeDetails = $detailsFondsRepository->findBy([
            'agence' => $agence,
            'devise' => $devise
        ]);
        
        $soldeTotal = 0;
        foreach ($soldeDetails as $detail) {
            $soldeTotal += (float)$detail->getMontant();
        }
        
        return $soldeTotal;
    }

    /**
     * Met à jour le solde d'une agence pour une devise donnée
     */
    private function updateSoldeAgence(DetailsFondsDepartRepository $detailsFondsRepository, EntityManagerInterface $em, $agence, $devise, float $montant): void
    {
        // Récupérer les détails de fonds existants
        $soldeDetails = $detailsFondsRepository->findBy([
            'agence' => $agence,
            'devise' => $devise
        ]);
        
        if (!empty($soldeDetails)) {
            // Mettre à jour le premier détail existant
            $detail = $soldeDetails[0];
            $nouveauSolde = (float)$detail->getMontant() + $montant;
            $detail->setMontant((string)$nouveauSolde);
            $em->persist($detail);
        }
        
        $em->flush();
    }
}

