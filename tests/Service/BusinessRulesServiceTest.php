<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Article\CreateArticleDTO;
use App\DTO\Category\CreateCategoryDTO;
use App\DTO\Demande\CreateDemandeDTO;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Demande;
use App\Exception\ArticleBusinessException;
use App\Exception\CategoryBusinessException;
use App\Exception\DemandeBusinessException;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\DemandeRepository;
use App\Service\Article\ArticleService;
use App\Service\Category\CategoryService;
use App\Service\Demande\DemandeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BusinessRulesServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private ArticleRepository $articleRepository;
    private DemandeRepository $demandeRepository;

    private CategoryService $categoryService;
    private ArticleService $articleService;
    private DemandeService $demandeService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->articleRepository = $this->createMock(ArticleRepository::class);
        $this->demandeRepository = $this->createMock(DemandeRepository::class);

        $this->categoryService = new CategoryService($this->entityManager, $this->categoryRepository);
        $this->articleService = new ArticleService($this->entityManager, $this->articleRepository);
        $this->demandeService = new DemandeService($this->entityManager, $this->demandeRepository);
    }

    public function testCategoryCannotBeItsOwnParent(): void
    {
        $category = new Category();
        $category->setName('Mode');

        $this->expectException(CategoryBusinessException::class);
        $this->expectExceptionMessage('ne peut pas être son propre parent');

        $this->categoryService->validateParentAssignment($category, $category);
    }

    public function testCategoryCannotHaveCyclicAncestor(): void
    {
        $grandParent = (new Category())->setName('Vêtements');
        $parent = (new Category())->setName('Hommes');
        $child = (new Category())->setName('T-Shirts');

        $parent->setParent($grandParent);
        $child->setParent($parent);

        $this->expectException(CategoryBusinessException::class);
        $this->expectExceptionMessage('boucle hiérarchique cyclique');

        // Tenter de faire de $child le parent de $grandParent
        $this->categoryService->validateParentAssignment($grandParent, $child);
    }

    public function testCannotAssignParentIfParentHasArticles(): void
    {
        $parent = (new Category())->setName('Chaussures');
        $article = (new Article())->setName('Baskets')->setPrice('89.90');
        $parent->addArticle($article);

        $child = (new Category())->setName('Running');

        $this->expectException(CategoryBusinessException::class);
        $this->expectExceptionMessage('contient déjà des articles');

        $this->categoryService->validateParentAssignment($child, $parent);
    }

    public function testCannotDeleteCategoryWithChildren(): void
    {
        $parent = (new Category())->setName('Informatique');
        $child = (new Category())->setName('Portables');
        $parent->addChild($child);

        $this->expectException(CategoryBusinessException::class);
        $this->expectExceptionMessage('contient des sous-catégories');

        $this->categoryService->delete($parent);
    }

    public function testCannotDeleteCategoryWithArticles(): void
    {
        $category = (new Category())->setName('Smartphones');
        $article = (new Article())->setName('iPhone')->setPrice('999.00');
        $category->addArticle($article);

        $this->expectException(CategoryBusinessException::class);
        $this->expectExceptionMessage('contient des articles');

        $this->categoryService->delete($category);
    }

    public function testArticleMustBeAssociatedToLeafCategoryOnly(): void
    {
        $parent = (new Category())->setName('Sport');
        $child = (new Category())->setName('Football');
        $parent->addChild($child);

        $this->expectException(ArticleBusinessException::class);
        $this->expectExceptionMessage('catégorie finale (feuille)');

        $this->articleService->validateCategory($parent);
    }

    public function testArticleCannotBeAssociatedToInactiveCategory(): void
    {
        $category = (new Category())->setName('Saisonnier')->setActive(false);

        $this->expectException(ArticleBusinessException::class);
        $this->expectExceptionMessage('inactive');

        $this->articleService->validateCategory($category);
    }

    public function testArticlePriceMustBeStrictlyPositive(): void
    {
        $this->expectException(ArticleBusinessException::class);
        $this->expectExceptionMessage('invalide');

        $this->articleService->validatePrice('0.00');
    }

    public function testCannotDeleteArticleWithDemandes(): void
    {
        $article = (new Article())->setName('Montre Luxe')->setPrice('250.00');
        $demande = (new Demande())->setClientName('Alice')->setPhone('0601020304')->setCity('Paris');
        $article->addDemande($demande);

        $this->expectException(ArticleBusinessException::class);
        $this->expectExceptionMessage('demandes clients historiques');

        $this->articleService->delete($article);
    }

    public function testDemandeRejectsUnavailableArticle(): void
    {
        $category = (new Category())->setName('Bijoux')->setActive(true);
        $article = (new Article())
            ->setName('Bague')
            ->setPrice('150.00')
            ->setCategory($category)
            ->setAvailable(false);

        $this->expectException(DemandeBusinessException::class);
        $this->expectExceptionMessage('n\'est actuellement pas disponible');

        $this->demandeService->validateArticleForDemande($article);
    }

    public function testDemandeRejectsArticleWithInactiveCategory(): void
    {
        $category = (new Category())->setName('Accessoires')->setActive(false);
        $article = (new Article())
            ->setName('Ceinture')
            ->setPrice('30.00')
            ->setCategory($category)
            ->setAvailable(true);

        $this->expectException(DemandeBusinessException::class);
        $this->expectExceptionMessage('désactivée');

        $this->demandeService->validateArticleForDemande($article);
    }
}

