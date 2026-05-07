<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        // Fetch real data from database
        $featuredPost = $postRepository->findFeatured();
        $posts = $postRepository->findLatest();

        return $this->render('home/index.html.twig', [
            'featuredPost' => $featuredPost,
            'posts' => $posts
        ]);
    }
    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
