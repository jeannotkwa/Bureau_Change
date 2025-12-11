<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rapports')]
class RapportController extends AbstractController
{
    #[Route('/', name: 'app_rapport_index')]
    public function index(): Response
    {
        return $this->render('rapport/index.html.twig');
    }
}
