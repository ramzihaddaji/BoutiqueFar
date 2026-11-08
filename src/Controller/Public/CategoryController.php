<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categories', name: 'public_category_')]
class CategoryController extends AbstractController
{
    /**
     * Liste des catégories principales (racines) avec leur arbre hiérarchique.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $rootCategories = $categoryRepository->findCategoryTree(true);

        return $this->render('public/category/index.html.twig', [
            'root_categories' => $rootCategories,
        ]);
    }

    /**
     * Détail d'une catégorie : affiche ses sous-catégories et, si c'est une catégorie feuille,
     * ses articles disponibles avec pagination.
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        Request $request,
        CategoryRepository $categoryRepository,
        ArticleRepository $articleRepository
    ): Response {
        /** @var Category|null $category */
        $category = $categoryRepository->find($id);

        if ($category === null || !$category->isActive()) {
            throw new NotFoundHttpException('Cette catégorie est introuvable ou inactive.');
        }

        // Sous-catégories directes actives
        $children = $categoryRepository->findChildrenByParent($category, true);

        // Si catégorie feuille : articles paginés
        $articles = [];
        $totalArticles = 0;
        $currentPage = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $maxPages = 1;

        if ($category->isLeaf()) {
            $paginator = $articleRepository->paginatePublicArticles($currentPage, $limit, $category);
            $totalArticles = count($paginator);
            $maxPages = (int) ceil($totalArticles / $limit);
            $articles = iterator_to_array($paginator);
        }

        // Construction du fil d'Ariane ascendant pour la navigation
        $breadcrumbs = [];
        $current = $category;
        while ($current !== null) {
            array_unshift($breadcrumbs, $current);
            $current = $current->getParent();
        }

        return $this->render('public/category/show.html.twig', [
            'category' => $category,
            'children' => $children,
            'articles' => $articles,
            'breadcrumbs' => $breadcrumbs,
            'current_page' => $currentPage,
            'max_pages' => max(1, $maxPages),
            'total_articles' => $totalArticles,
        ]);
    }
}

