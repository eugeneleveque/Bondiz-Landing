<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\ContactMessage;
use App\Entity\PartnershipRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\PartnershipRequestRepository;
use App\Repository\PostRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly CategoryRepository $categories,
        private readonly PartnershipRequestRepository $partnershipRequests,
        private readonly ContactMessageRepository $contactMessages,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $aTraiter = ['handled' => false];
        $recents = ['createdAt' => 'DESC'];

        return $this->render('admin/dashboard.html.twig', [
            'demandesEnAttente' => $this->partnershipRequests->count($aTraiter),
            'messagesEnAttente' => $this->contactMessages->count($aTraiter),
            'nombreArticles' => $this->posts->count([]),
            'nombreCategories' => $this->categories->count([]),
            'demandes' => $this->partnershipRequests->findBy($aTraiter, $recents, 5),
            'messages' => $this->contactMessages->findBy($aTraiter, $recents, 5),
            'articles' => $this->posts->findBy([], $recents, 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<span class="bz-admin-logo"><img src="/images/brand/logo-fantome.svg" alt=""> BONDIZ</span>')
            ->setFaviconPath('images/favicon.ico')
            // Les couleurs de la marque sont pensées pour un fond clair.
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead('<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;900&display=swap" rel="stylesheet">')
            ->addCssFile('css/admin.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Post::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Category::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToRoute('Partenaires', 'fas fa-store', 'admin_partners');
        yield MenuItem::linkToCrud('Demandes de partenariat', 'fas fa-handshake', PartnershipRequest::class);
        yield MenuItem::linkToCrud('Messages de contact', 'fas fa-envelope', ContactMessage::class);
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'app_home');
    }
}
