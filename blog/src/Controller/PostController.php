<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostController extends AbstractController
{
    #[Route('/posts', name: 'app_post_index')]
    public function index(PostRepository $postRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        $categoryId = $request->query->get('category');
        $queryBuilder = $postRepository->findAllQueryBuilder();

        if ($categoryId) {
            $queryBuilder->andWhere('p.category = :category')
                ->setParameter('category', $categoryId);
        }

        $posts = $queryBuilder->getQuery()->getResult();
        $categories = $categoryRepository->findAll();
        $featuredPost = $postRepository->findFeatured();

        return $this->render('post/posts.html.twig', [
            'posts' => $posts,
            'categories' => $categories,
            'currentCategory' => $categoryId,
            'featuredPost' => $featuredPost
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show')]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }
}
