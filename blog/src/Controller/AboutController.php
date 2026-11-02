<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page « À propos ». Elle était servie par la landing React ; elle passe dans
 * le blog pour partager l'en-tête, le pied de page et design.css.
 */
class AboutController extends AbstractController
{
    #[Route('/a-propos', name: 'app_a_propos')]
    public function index(): Response
    {
        return $this->render('about/a_propos.html.twig');
    }
}
