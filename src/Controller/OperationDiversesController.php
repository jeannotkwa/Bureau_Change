<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\DetailsFondsDepart;
use App\Entity\DetailsTransaction;
use App\Entity\FondsDepart;
use App\Repository\DeviseRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\AgenceRepository;
use App\Repository\DetailsFondsDepartRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/operations-diverses')]
#[IsGranted('ROLE_ADMIN')]
class OperationDiversesController extends AbstractController
{
    #[Route('/', name: 'app_operation_diverse_index')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        // Récupérer les opérations diverses (nature_operation = 'Autre')
        $operations = $transactionRepository->findBy(
            ['natureOperation' => 'Autre'],
            ['dateTransaction' => 'DESC'],
            50
        );

        return $this->render('operation_diverses/index.html.twig', [
            'operations' => $operations,
        ]);
    }

    #[Route('/nouvelle', name: 'app_operation_diverse_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        DeviseRepository $deviseRepository,
        UtilisateurRepository $utilisateurRepository,
        AgenceRepository $agenceRepository,
        DetailsFondsDepartRepository $detailsFondsRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'agence et l'utilisateur depuis la session
        $agenceUtilisateur = null;
        if (method_exists($user, 'getAgence')) {
            $agenceUtilisateur = $user->getAgence();
        }
        
        if (!$agenceUtilisateur) {
            $this->addFlash('error', 'Aucune agence assignée à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Load devises
        $devises = $deviseRepository->findBy(['statut' => 'Actif'], ['sigle' => 'ASC']);

        // Handle POST request
        if ($request->isMethod('POST')) {
            $errors = [];

            $date = $request->request->get('date');
            $montant = floatval($request->request->get('montant') ?? 0);
            $devise_id = $request->request->get('devise');
            $motif = $request->request->get('motif');
            $beneficiaire = $request->request->get('beneficiaire');
            $numero_piece = $request->request->get('numero_piece');
            
            // Forcer agence depuis la session (pas de formulaire)
            $agence_id = $agenceUtilisateur->getId();

            // Validation
            if (!$date) $errors[] = "La date est requise.";
            if (!$montant || $montant <= 0) $errors[] = "Le montant est invalide.";
            if (!$devise_id) $errors[] = "Veuillez sélectionner une devise.";
            if (!$motif) $errors[] = "Le motif est requis.";
            if (!$beneficiaire) $errors[] = "Le nom du bénéficiaire est requis.";

            // Handle file upload
            $imagePath = null;
            $uploadedFile = $request->files->get('image_document');
            if ($uploadedFile) {
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                $fileExt = strtolower($uploadedFile->getClientOriginalExtension());

                if (!in_array($fileExt, $allowed)) {
                    $errors[] = "Format de fichier non autorisé. (JPG, PNG, PDF uniquement)";
                } else {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
                    if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0777, true);
                    $newName = uniqid() . '.' . $fileExt;
                    $uploadedFile->move($uploadsDir, $newName);
                    $imagePath = 'uploads/' . $newName;
                }
            }

            // Check date is not in the future
            if (!$errors) {
                $dateOperation = new \DateTime($date);
                $today = new \DateTime();
                $today->setTime(0, 0, 0);
                $dateOperation->setTime(0, 0, 0);
                
                if ($dateOperation > $today) {
                    $errors[] = "❌ Erreur : Impossible de créer une opération avec une date future. Veuillez sélectionner une date d'aujourd'hui ou antérieure.";
                }
            }
            
            // Check balance if no validation errors
            if (!$errors) {
                $devise = $deviseRepository->find($devise_id);
                $agence = $agenceRepository->find($agence_id);

                if (!$devise || !$agence) {
                    $errors[] = "Devise ou agence invalide.";
                } else {
                    // Vérifier le solde total (SUM de tous les détails) pour cette agence et devise
                    $montant_disponible = $detailsFondsRepository->getSoldeByAgenceAndDevise($agence->getId(), $devise->getId());

                    if ($montant > $montant_disponible) {
                        $errors[] = "💰 Fonds insuffisants pour cette devise : demandé $montant, disponible $montant_disponible.";
                    }
                }
            }

            // If errors, render form with errors
            if ($errors) {
                return $this->render('operation_diverses/form.html.twig', [
                    'devises' => $devises,
                    'agence_utilisateur' => $agenceUtilisateur,
                    'utilisateur' => $user,
                    'errors' => $errors,
                    'form_data' => [
                        'date' => $date,
                        'montant' => $montant,
                        'devise' => $devise_id,
                        'motif' => $motif,
                        'beneficiaire' => $beneficiaire,
                        'numero_piece' => $numero_piece,
                    ],
                ]);
            }

            try {
                // Create transaction
                $transaction = new Transaction();
                $transaction->setNatureOperation('Autre');
                $transaction->setReference('TRANS-D-' . date('Ymd-His') . '-' . $agence_id);
                $transaction->setDateTransaction(new \DateTime($date));
                $transaction->setNom($beneficiaire);
                $transaction->setTelephone($numero_piece);
                $transaction->setAdresse($motif);

                // Utiliser l'utilisateur et l'agence de la session
                $transaction->setUtilisateur($user);
                $transaction->setAgence($agenceUtilisateur);

                $em->persist($transaction);
                $em->flush();

                // Créer un FondsDepart avec ses détails pour cette opération diverse
                $fondsDepart = new FondsDepart();
                $fondsDepart->setAgence($agenceUtilisateur);
                $fondsDepart->setDateJour(new \DateTime());
                $fondsDepart->setStatut('ferme');
                $em->persist($fondsDepart);

                // Créer un détail de fonds avec montant négatif (sortie)
                $detailFonds = new DetailsFondsDepart();
                $detailFonds->setFondsDepart($fondsDepart);
                $detailFonds->setDevise($devise);
                $detailFonds->setMontant((string)(-$montant)); // Négatif car c'est une sortie
                $detailFonds->setAgence($agenceUtilisateur);
                $em->persist($detailFonds);
                $fondsDepart->addDetail($detailFonds);

                // Créer aussi un DetailsTransaction pour afficher le montant et devise dans la liste
                $detailTransaction = new DetailsTransaction();
                $detailTransaction->setTransaction($transaction);
                $detailTransaction->setDeviseInput($devise);
                $detailTransaction->setDeviseOutput($devise);
                $detailTransaction->setMontant((string)$montant);
                $detailTransaction->setTaux('1.0000');
                $detailTransaction->setContreValeur((string)$montant);
                $em->persist($detailTransaction);
                $transaction->addDetail($detailTransaction);
                $em->flush();

                $this->addFlash('success', '✅ L\'opération a été enregistrée avec succès.');
                return $this->redirectToRoute('app_operation_diverse_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', '❌ Erreur lors de l\'enregistrement : ' . $e->getMessage());
                return $this->redirectToRoute('app_operation_diverse_new');
            }
        }

        return $this->render('operation_diverses/form.html.twig', [
            'devises' => $devises,
            'agence_utilisateur' => $agenceUtilisateur,
            'utilisateur' => $user,
            'errors' => [],
            'form_data' => [],
        ]);
    }
}
