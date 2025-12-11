<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\DetailsTransaction;
use App\Entity\FondsDepart;
use App\Repository\AgenceRepository;
use App\Repository\DeviseRepository;
use App\Repository\TransactionRepository;
use App\Repository\TypeIdentiteRepository;
use App\Repository\DetailsFondsDepartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transferts')]
#[IsGranted('ROLE_ADMIN')]
class TransfertFondController extends AbstractController
{
    #[Route('/', name: 'app_transfert_index')]
    public function index(TransactionRepository $transactionRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les derniers transferts
        $transferts = $transactionRepository->findBy(
            ['natureOperation' => ['envoi', 'reception']],
            ['createdAt' => 'DESC'],
            50
        );
        
        // Générer les alertes pour l'audit
        $alertes = $this->genererAlertesAudit($transactionRepository);

        return $this->render('transfert/index.html.twig', [
            'transferts' => $transferts,
            'alertes' => $alertes,
        ]);
    }
    
    /**
     * Génère les alertes d'audit pour les transferts non réceptionnés ou partiellement réceptionnés
     */
    private function genererAlertesAudit(TransactionRepository $transactionRepository): array
    {
        $alertes = [];
        
        // Récupérer tous les envois
        $envois = $transactionRepository->findBy(['natureOperation' => 'envoi'], ['dateTransaction' => 'DESC']);
        
        foreach ($envois as $envoi) {
            $reference = $envoi->getReference();
            
            // Calculer le montant total envoyé par devise
            $montantsEnvoyes = [];
            foreach ($envoi->getDetails() as $detail) {
                $deviseId = $detail->getDeviseInput()->getId();
                $montant = (float)$detail->getMontant();
                
                if (!isset($montantsEnvoyes[$deviseId])) {
                    $montantsEnvoyes[$deviseId] = [
                        'devise' => $detail->getDeviseInput()->getSigle(),
                        'montant' => 0
                    ];
                }
                $montantsEnvoyes[$deviseId]['montant'] += $montant;
            }
            
            // Chercher les réceptions correspondantes
            $receptions = $transactionRepository->findBy([
                'reference' => $reference,
                'natureOperation' => 'reception'
            ]);
            
            // Calculer le montant total reçu par devise
            $montantsRecus = [];
            foreach ($receptions as $reception) {
                foreach ($reception->getDetails() as $detail) {
                    $deviseId = $detail->getDeviseInput()->getId();
                    $montant = (float)$detail->getMontant();
                    
                    if (!isset($montantsRecus[$deviseId])) {
                        $montantsRecus[$deviseId] = 0;
                    }
                    $montantsRecus[$deviseId] += $montant;
                }
            }
            
            // Comparer les montants
            foreach ($montantsEnvoyes as $deviseId => $data) {
                $montantEnvoye = $data['montant'];
                $montantRecu = $montantsRecus[$deviseId] ?? 0;
                
                if ($montantRecu < $montantEnvoye) {
                    $alertes[] = [
                        'type' => $montantRecu == 0 ? 'danger' : 'warning',
                        'reference' => $reference,
                        'devise' => $data['devise'],
                        'montant_envoye' => $montantEnvoye,
                        'montant_recu' => $montantRecu,
                        'montant_manquant' => $montantEnvoye - $montantRecu,
                        'date_envoi' => $envoi->getDateTransaction(),
                        'agence_emettrice' => $envoi->getAgence()->getNomAgence(),
                        'statut' => $montantRecu == 0 ? 'Non réceptionné' : 'Partiellement réceptionné'
                    ];
                }
            }
        }
        
        return $alertes;
    }

    #[Route('/nouveau', name: 'app_transfert_new')]
    public function new(
        Request $request, 
        EntityManagerInterface $em, 
        AgenceRepository $agenceRepository,
        DeviseRepository $deviseRepository,
        TypeIdentiteRepository $typeIdentiteRepository,
        DetailsFondsDepartRepository $detailsFondsRepo,
        TransactionRepository $transactionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Charger les devises
        $devises = $deviseRepository->findBy(['statut' => 'Actif'], ['sigle' => 'ASC']);

        // Récupérer l'agence de l'utilisateur connecté
        $agenceActuelle = null;
        if (method_exists($user, 'getAgence')) {
            $agenceActuelle = $user->getAgence();
        }

        // Charger les agences SAUF celle de l'utilisateur
        $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);
        if ($agenceActuelle) {
            $agences = array_filter($agences, function($agence) use ($agenceActuelle) {
                return $agence->getId() !== $agenceActuelle->getId();
            });
        }

        if ($request->isMethod('POST')) {
            // Traitement du formulaire de transfert
            try {
                $natureOperation = $request->request->get('nature_operation');
                $agenceCible = $request->request->get('agence_cible');
                $reference = $request->request->get('reference', '');
                $dateStr = $request->request->get('date_transaction');
                $montants = $request->request->all('montant');
                $devisesId = $request->request->all('devise_id_input');

                // Validation
                if (empty($natureOperation) || empty($agenceCible)) {
                    throw new \Exception('Veuillez renseigner tous les champs obligatoires.');
                }

                if (!in_array($natureOperation, ['envoi', 'reception'])) {
                    throw new \Exception('La nature d\'opération est invalide.');
                }

                if ($natureOperation === 'reception' && empty($reference)) {
                    throw new \Exception('La référence est obligatoire pour une réception.');
                }
                
                // Si réception, vérifier que la référence existe côté envoi
                if ($natureOperation === 'reception') {
                    $envoi = $transactionRepository->findOneBy([
                        'reference' => $reference,
                        'natureOperation' => 'envoi'
                    ]);
                    
                    if (!$envoi) {
                        throw new \Exception('❌ Référence introuvable. Aucun envoi correspondant à cette référence n\'a été trouvé dans le système.');
                    }
                    
                    // Calculer le montant total déjà reçu pour cette référence
                    $receptionsExistantes = $transactionRepository->findBy([
                        'reference' => $reference,
                        'natureOperation' => 'reception'
                    ]);
                    
                    // Calculer les montants envoyés et déjà reçus par devise
                    $montantsEnvoyes = [];
                    foreach ($envoi->getDetails() as $detail) {
                        $deviseId = $detail->getDeviseInput()->getId();
                        if (!isset($montantsEnvoyes[$deviseId])) {
                            $montantsEnvoyes[$deviseId] = 0;
                        }
                        $montantsEnvoyes[$deviseId] += (float)$detail->getMontant();
                    }
                    
                    $montantsDejaRecus = [];
                    foreach ($receptionsExistantes as $reception) {
                        foreach ($reception->getDetails() as $detail) {
                            $deviseId = $detail->getDeviseInput()->getId();
                            if (!isset($montantsDejaRecus[$deviseId])) {
                                $montantsDejaRecus[$deviseId] = 0;
                            }
                            $montantsDejaRecus[$deviseId] += (float)$detail->getMontant();
                        }
                    }
                    
                    // Vérifier que les montants à réceptionner ne dépassent pas ce qui a été envoyé
                    foreach ($devisesId as $index => $deviseId) {
                        $montantAReceptionner = (float)($montants[$index] ?? 0);
                        if ($montantAReceptionner <= 0) continue;
                        
                        $montantEnvoye = $montantsEnvoyes[$deviseId] ?? 0;
                        $montantDejaRecu = $montantsDejaRecus[$deviseId] ?? 0;
                        $montantRestant = $montantEnvoye - $montantDejaRecu;
                        
                        if ($montantAReceptionner > $montantRestant) {
                            $devise = $deviseRepository->find($deviseId);
                            throw new \Exception(
                                "❌ Montant de réception invalide pour " . $devise->getSigle() . 
                                ". Envoyé: {$montantEnvoye}, Déjà reçu: {$montantDejaRecu}, Restant: {$montantRestant}. " .
                                "Vous tentez de réceptionner: {$montantAReceptionner}"
                            );
                        }
                    }
                }

                // Validation de la date
                $dateTransaction = new \DateTime();
                if (!empty($dateStr)) {
                    $dateTransaction = \DateTime::createFromFormat('Y-m-d', $dateStr);
                    if (!$dateTransaction) {
                        throw new \Exception('Format de date invalide.');
                    }
                }

                // Vérifier que la date n'est pas dans le futur
                $today = new \DateTime();
                $today->setTime(0, 0, 0);
                $dateTransaction->setTime(0, 0, 0);
                if ($dateTransaction > $today) {
                    throw new \Exception('❌ Erreur : Impossible de créer une transaction avec une date future. Veuillez sélectionner une date d\'aujourd\'hui ou antérieure.');
                }

                // Génération de la référence pour envoi si absent
                if ($natureOperation === 'envoi' && empty($reference)) {
                    // Utiliser microtime pour garantir l'unicité + un identifiant aléatoire
                    $microtime = microtime(true);
                    $timestamp = date('Ymd-His', (int)$microtime);
                    $microseconds = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
                    $randomSuffix = substr(md5(uniqid(mt_rand(), true)), 0, 4);
                    $reference = 'ENVOI-' . $timestamp . '-' . $microseconds . '-' . ($agenceActuelle ? $agenceActuelle->getId() : 'SYS') . '-' . strtoupper($randomSuffix);
                }

                // Vérifier qu'il y a au moins un montant
                $hasMontants = false;
                foreach ($montants as $index => $montant) {
                    if (!empty($montant) && is_numeric($montant) && $montant > 0) {
                        $hasMontants = true;
                        break;
                    }
                }

                if (!$hasMontants) {
                    throw new \Exception('Veuillez entrer au moins un montant valide.');
                }

                // Démarrer la transaction sur la connexion
                $em->getConnection()->beginTransaction();

                try {
                    // Utiliser un type d'identité par défaut pour les transferts internes
                    $typeIdentite = $typeIdentiteRepository->findOneBy([]);
                    if (!$typeIdentite) {
                        throw new \Exception('Aucun type d\'identité disponible dans le système.');
                    }

                    // Récupérer l'agence destination
                    $agenceDestination = $agenceRepository->find($agenceCible);
                    if (!$agenceDestination) {
                        throw new \Exception('Agence destination non trouvée.');
                    }

                // Créer la transaction principale
                $transaction = new Transaction();
                $transaction->setReference($reference);
                $transaction->setNom($reference);
                $transaction->setAdresse('Transfert inter-agences');
                $transaction->setTelephone('000-0000');
                $transaction->setIdentite($typeIdentite);
                $transaction->setNatureOperation($natureOperation);
                $transaction->setDateTransaction($dateTransaction);
                $transaction->setUtilisateur($user);
                
                // Définir l'agence source selon la nature
                if ($natureOperation === 'envoi') {
                    // Pour un envoi, l'agence est l'agence source (agence actuelle)
                    if (!$agenceActuelle) {
                        throw new \Exception('Agence source non définie.');
                    }
                    $transaction->setAgence($agenceActuelle);
                } else {
                    // Pour une réception, l'agence est l'agence destination
                    $transaction->setAgence($agenceDestination);
                }

                $em->persist($transaction);

                // Traitement de chaque devise
                foreach ($montants as $index => $montant) {
                    $deviseId = $devisesId[$index] ?? null;
                    if (!$deviseId || !is_numeric($montant) || $montant <= 0) {
                        continue;
                    }

                    $montant = (float)$montant;

                    // Récupérer la devise
                    $devise = $deviseRepository->find($deviseId);
                    if (!$devise) {
                        continue;
                    }

                    if ($natureOperation === 'envoi') {
                        // **ENVOI** : Débiter l'agence source
                        
                        // Calculer le solde total disponible à l'agence source (somme de tous les détails)
                        $detailsSoldes = $detailsFondsRepo->findBy([
                            'agence' => $agenceActuelle,
                            'devise' => $devise
                        ]);

                        $soldeDisponible = 0;
                        foreach ($detailsSoldes as $detail) {
                            $soldeDisponible += (float)$detail->getMontant();
                        }
                        
                        // Vérifier que le solde est supérieur ou égal au montant à envoyer
                        if ($soldeDisponible < $montant) {
                            throw new \Exception(
                                "❌ Solde insuffisant pour l'envoi de {$montant} " . $devise->getSigle() . 
                                ". Solde disponible à l'agence {$agenceActuelle->getNomAgence()}: {$soldeDisponible}"
                            );
                        }

                        // Réduire le montant du solde disponible de l'agence émetteur
                        // On déduit le montant des détails existants (en commençant par le premier)
                        $montantRestant = $montant;
                        foreach ($detailsSoldes as $detail) {
                            if ($montantRestant <= 0) break;
                            
                            $montantDetail = (float)$detail->getMontant();
                            if ($montantDetail >= $montantRestant) {
                                // Ce détail suffit pour couvrir le reste
                                $detail->setMontant((string)($montantDetail - $montantRestant));
                                $em->persist($detail);
                                $montantRestant = 0;
                            } else {
                                // Ce détail ne suffit pas, on le met à zéro et on continue
                                $detail->setMontant('0.00');
                                $em->persist($detail);
                                $montantRestant -= $montantDetail;
                            }
                        }

                        // Enregistrer le mouvement de fonds (envoi) dans FondsDepart avec montant NÉGATIF
                        $fondsDepart = new FondsDepart();
                        $fondsDepart->setAgence($agenceActuelle);
                        $fondsDepart->setDateJour($dateTransaction);
                        $fondsDepart->setStatut('ferme');
                        $em->persist($fondsDepart);

                        // Créer un détail avec montant négatif pour représenter la sortie
                        $detailFonds = new \App\Entity\DetailsFondsDepart();
                        $detailFonds->setFondsDepart($fondsDepart);
                        $detailFonds->setDevise($devise);
                        $detailFonds->setMontant((string)(-$montant)); // MONTANT NÉGATIF pour l'envoi
                        $detailFonds->setAgence($agenceActuelle);
                        $em->persist($detailFonds);
                        $fondsDepart->addDetail($detailFonds);

                    } elseif ($natureOperation === 'reception') {
                        // **RÉCEPTION** : Créditer l'agence de l'utilisateur connecté (pas l'agence destination)
                        
                        // Enregistrer le mouvement de fonds (réception) dans FondsDepart avec montant POSITIF
                        $fondsDepart = new FondsDepart();
                        $fondsDepart->setAgence($agenceActuelle);  // CORRECTION: agence de l'utilisateur, pas destination
                        $fondsDepart->setDateJour($dateTransaction);
                        $fondsDepart->setStatut('ferme');
                        $em->persist($fondsDepart);

                        $detailFonds = new \App\Entity\DetailsFondsDepart();
                        $detailFonds->setFondsDepart($fondsDepart);
                        $detailFonds->setDevise($devise);
                        $detailFonds->setMontant((string)$montant); // MONTANT POSITIF pour la réception
                        $detailFonds->setAgence($agenceActuelle);  // CORRECTION: agence de l'utilisateur, pas destination
                        $em->persist($detailFonds);
                        $fondsDepart->addDetail($detailFonds);
                    }

                    // Créer le détail de la transaction
                    $detail = new DetailsTransaction();
                    $detail->setTransaction($transaction);
                    $detail->setDeviseInput($devise);
                    $detail->setDeviseOutput($devise);
                    $detail->setMontant((string)$montant);
                    $detail->setTaux('1.0000');
                    $detail->setContreValeur((string)$montant);

                    $em->persist($detail);
                    $transaction->addDetail($detail);
                }

                $em->flush();
                $em->getConnection()->commit();
                
                $this->addFlash('success', '✅ Transfert enregistré avec succès.');
                return $this->redirectToRoute('app_transfert_index');

                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    throw $e;
                }

            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('transfert/form.html.twig', [
            'agences' => $agences,
            'devises' => $devises,
            'agenceActuelle' => $agenceActuelle,
            'title' => 'Nouveau transfert de fonds',
        ]);
    }
}
