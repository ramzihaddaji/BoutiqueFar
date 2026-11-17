<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Demande;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\DemandeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepositoryQueryTest extends KernelTestCase
{
    private CategoryRepository $categoryRepository;
    private ArticleRepository $articleRepository;
    private DemandeRepository $demandeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->categoryRepository = $container->get(CategoryRepository::class);
        $this->articleRepository = $container->get(ArticleRepository::class);
        $this->demandeRepository = $container->get(DemandeRepository::class);
    }

    public function testCategoryRepositoryMethodsDql(): void
    {
        $this->assertInstanceOf(CategoryRepository::class, $this->categoryRepository);

        // Vérifie la compilation DQL sans erreur de syntaxe ou d'alias
        $this->assertIsArray($this->categoryRepository->findRootCategories());
        $this->assertIsArray($this->categoryRepository->findActiveCategories());
        $this->assertIsArray($this->categoryRepository->findLeafCategories());
        $this->assertIsArray($this->categoryRepository->findCategoryTree());
    }

    public function testArticleRepositoryMethodsDql(): void
    {
        $this->assertInstanceOf(ArticleRepository::class, $this->articleRepository);

        $this->assertIsArray($this->articleRepository->findAvailableArticles());
        $this->assertIsArray($this->articleRepository->findLatestArticles(5));
        $this->assertIsArray($this->articleRepository->findInStockForShowcase(12));
        $this->assertIsArray($this->articleRepository->searchArticles('test'));
        $this->assertNull($this->articleRepository->findAvailableById(999999));
    }

    public function testDemandeRepositoryMethodsDql(): void
    {
        $this->assertInstanceOf(DemandeRepository::class, $this->demandeRepository);

        $this->assertIsArray($this->demandeRepository->findLatestDemandes(10));
        $this->assertIsArray($this->demandeRepository->countByArticle());
    }
}

