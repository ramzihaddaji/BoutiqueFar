<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'public_home', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository,
        ArticleRepository $articleRepository
    ): Response {
        // Catégories racines actives
        $rootCategories = $categoryRepository->findRootCategories(true);

        // Derniers articles disponibles dans des catégories actives
        $latestArticles = $articleRepository->findLatestArticles(8, true);

        // Articles populaires (ayant reçu le plus de demandes)
        $popularArticles = $articleRepository->findPopularArticles(6);

        return $this->render('public/home/index.html.twig', [
            'root_categories' => $rootCategories,
            'latest_articles' => $latestArticles,
            'popular_articles' => $popularArticles,
        ]);
    }
}

