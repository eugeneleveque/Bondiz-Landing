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
    /** Articles affichés sous l'article à la une, puis à chaque « Voir plus ». */
    private const ARTICLES_PAR_PAGE = 4;

    #[Route('/posts', name: 'app_post_index')]
    public function index(PostRepository $postRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        $categoryId = $request->query->getInt('category') ?: null;
        $page = max(1, $request->query->getInt('page', 1));

        $queryBuilder = $postRepository->findAllQueryBuilder();
        if ($categoryId) {
            $queryBuilder->andWhere('p.category = :category')
                ->setParameter('category', $categoryId);
        }
        $posts = $queryBuilder->getQuery()->getResult();

        // Grande carte : l'article à la une, ou le plus récent de la catégorie.
        $featuredPost = $categoryId ? null : $postRepository->findFeatured();
        $featuredPost ??= $posts[0] ?? null;
        $posts = array_values(array_filter($posts, fn (Post $post) => $post !== $featuredPost));

        $visible = self::ARTICLES_PAR_PAGE * $page;

        return $this->render('post/posts.html.twig', [
            'posts' => \array_slice($posts, 0, $visible),
            'hasMore' => \count($posts) > $visible,
            'page' => $page,
            'categories' => $categoryRepository->findAll(),
            'currentCategory' => $categoryId,
            'featuredPost' => $featuredPost,
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
