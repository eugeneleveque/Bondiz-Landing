<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/home.html.twig');
    }
    #[Route('/qui-sommes-nous', name: 'app_about')]
    public function about(): Response
    {
        // Le contenu « Pourquoi avons-nous créé Bondiz ? » vit désormais sur /a-propos (landing React)
        return $this->redirect('/a-propos', Response::HTTP_MOVED_PERMANENTLY);
    }
}
