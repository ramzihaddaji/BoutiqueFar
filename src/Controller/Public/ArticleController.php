<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/articles', name: 'public_article_')]
class ArticleController extends AbstractController
{
    /**
     * Catalogue public avec pagination, filtrage par catégorie et recherche par mot-clé.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $search = $request->query->get('q');
        $categoryId = $request->query->getInt('category');

        $selectedCategory = null;
        if ($categoryId > 0) {
            $selectedCategory = $categoryRepository->find($categoryId);
            if ($selectedCategory === null || !$selectedCategory->isActive()) {
                $selectedCategory = null;
            }
        }

        $paginator = $articleRepository->paginatePublicArticles(
            $page,
            $limit,
            $selectedCategory,
            $search !== null ? (string) $search : null
        );

        $totalArticles = count($paginator);
        $maxPages = (int) ceil($totalArticles / $limit);

        // Catégories feuilles actives pour le sélecteur de filtrage
        $leafCategories = $categoryRepository->findLeafCategories(true);

        return $this->render('public/article/index.html.twig', [
            'articles' => iterator_to_array($paginator),
            'current_page' => $page,
            'max_pages' => max(1, $maxPages),
            'total_articles' => $totalArticles,
            'search_query' => $search,
            'selected_category' => $selectedCategory,
            'leaf_categories' => $leafCategories,
        ]);
    }

    /**
     * Fiche détaillée d'un article disponible.
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findAvailableById($id);

        if ($article === null) {
            throw new NotFoundHttpException('Cet article est introuvable ou n\'est plus disponible.');
        }

        // Catégorie et fil d'Ariane
        $breadcrumbs = [];
        $current = $article->getCategory();
        while ($current !== null) {
            array_unshift($breadcrumbs, $current);
            $current = $current->getParent();
        }

        return $this->render('public/article/show.html.twig', [
            'article' => $article,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}

