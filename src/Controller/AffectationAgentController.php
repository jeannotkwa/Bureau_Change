<?php

namespace App\Controller;

use App\Entity\AffectationAgent;
use App\Entity\Utilisateur;
use App\Repository\AffectationAgentRepository;
use App\Repository\AgenceRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/affectations-agents')]
class AffectationAgentController extends AbstractController
{
    #[Route('/', name: 'app_affectation_agent_index')]
    public function index(AffectationAgentRepository $affectationRepo, UtilisateurRepository $utilisateurRepo): Response
    {
        $agents = $utilisateurRepo->findBy(['statut' => 'actif'], ['nom' => 'ASC']);
        
        $agentsAvecAffectation = [];
        foreach ($agents as $agent) {
            $affectationActuelle = $affectationRepo->getAffectationActuelle($agent);
            $agentsAvecAffectation[] = [
                'agent' => $agent,
                'affectation' => $affectationActuelle,
            ];
        }

        return $this->render('affectation_agent/index.html.twig', [
            'agentsAvecAffectation' => $agentsAvecAffectation,
        ]);
    }

    #[Route('/transferer/{id}', name: 'app_affectation_agent_transferer')]
    public function transferer(
        Utilisateur $utilisateur,
        Request $request,
        AffectationAgentRepository $affectationRepo,
        AgenceRepository $agenceRepo,
        EntityManagerInterface $em
    ): Response {
        $agences = $agenceRepo->findBy([], ['nomAgence' => 'ASC']);
        $affectationActuelle = $affectationRepo->getAffectationActuelle($utilisateur);
        $historique = $affectationRepo->getHistoriqueAffectations($utilisateur);

        if ($request->isMethod('POST')) {
            $agenceId = $request->request->get('agence_id');
            $dateDebut = $request->request->get('date_debut');
            $agence = $agenceRepo->find($agenceId);

            if (!$agence) {
                $this->addFlash('danger', '❌ Agence invalide');
                return $this->redirectToRoute('app_affectation_agent_transferer', ['id' => $utilisateur->getId()]);
            }

            if (!$dateDebut) {
                $this->addFlash('danger', '❌ La date de début est requise');
                return $this->redirectToRoute('app_affectation_agent_transferer', ['id' => $utilisateur->getId()]);
            }

            try {
                // Clôturer l'affectation précédente
                if ($affectationActuelle) {
                    $affectationActuelle->setDateFin(new \DateTime($dateDebut));
                    $affectationActuelle->setStatut('inactif');
                    $em->persist($affectationActuelle);
                }

                // Créer la nouvelle affectation
                $nouvelleAffectation = new AffectationAgent();
                $nouvelleAffectation->setUtilisateur($utilisateur);
                $nouvelleAffectation->setAgence($agence);
                $nouvelleAffectation->setDateDebut(new \DateTime($dateDebut));
                $nouvelleAffectation->setStatut('actif');
                $em->persist($nouvelleAffectation);

                // Mettre à jour l'agence de l'utilisateur
                $utilisateur->setAgence($agence);
                $em->persist($utilisateur);

                $em->flush();

                $this->addFlash('success', "✅ L'agent {$utilisateur->getNom()} a été transféré à {$agence->getNomAgence()}");
                return $this->redirectToRoute('app_affectation_agent_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', '❌ Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('affectation_agent/transferer.html.twig', [
            'utilisateur' => $utilisateur,
            'agences' => $agences,
            'affectationActuelle' => $affectationActuelle,
            'historique' => $historique,
        ]);
    }

    #[Route('/historique/{id}', name: 'app_affectation_agent_historique')]
    public function historique(Utilisateur $utilisateur, AffectationAgentRepository $affectationRepo): Response
    {
        $historique = $affectationRepo->getHistoriqueAffectations($utilisateur);

        return $this->render('affectation_agent/historique.html.twig', [
            'utilisateur' => $utilisateur,
            'historique' => $historique,
        ]);
    }

    #[Route('/agence/{id}', name: 'app_affectation_agent_par_agence')]
    public function parAgence($id, AffectationAgentRepository $affectationRepo, AgenceRepository $agenceRepo): Response
    {
        $agence = $agenceRepo->find($id);
        if (!$agence) {
            throw $this->createNotFoundException('Agence non trouvée');
        }

        $agents = $affectationRepo->getAgentsParAgence($agence);
        $historique = $affectationRepo->getHistoriqueAffectationsAgence($agence);

        return $this->render('affectation_agent/par_agence.html.twig', [
            'agence' => $agence,
            'agents' => $agents,
            'historique' => $historique,
        ]);
    }
}
