<?php

namespace App\Controller;

use App\Partner\PublicPartners;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page publique de présentation des partenaires. À ne pas confondre avec
 * l'espace partenaire (/partenaires), réservé aux partenaires connectés.
 */
class OurPartnersController extends AbstractController
{
    #[Route('/nos-partenaires', name: 'app_nos_partenaires')]
    public function index(PublicPartners $publicPartners): Response
    {
        return $this->render('partners/nos_partenaires.html.twig', [
            'partenaires' => $publicPartners->list(),
        ]);
    }
}
