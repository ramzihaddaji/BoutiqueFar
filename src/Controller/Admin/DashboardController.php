<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\DemandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository,
        ArticleRepository $articleRepository,
        DemandeRepository $demandeRepository
    ): Response {
        $totalCategories = $categoryRepository->count([]);
        $totalArticles = $articleRepository->count([]);
        $availableArticles = count($articleRepository->findAvailableArticles());
        $latestDemandes = $demandeRepository->findLatestDemandes(10);

        return $this->render('admin/dashboard/index.html.twig', [
            'total_categories' => $totalCategories,
            'total_articles' => $totalArticles,
            'available_articles' => $availableArticles,
            'latest_demandes' => $latestDemandes,
        ]);
    }
}

