<?php

declare(strict_types=1);

namespace App\Service\Category;

use App\DTO\Category\CreateCategoryDTO;
use App\DTO\Category\UpdateCategoryDTO;
use App\Entity\Category;
use App\Exception\CategoryBusinessException;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * Crée une nouvelle catégorie ou sous-catégorie.
     *
     * @throws CategoryBusinessException
     */
    public function create(CreateCategoryDTO $dto): Category
    {
        $category = new Category();
        $category->setName($dto->name);
        $category->setDescription($dto->description);
        $category->setActive($dto->active);

        if ($dto->parent !== null) {
            $this->validateParentAssignment($category, $dto->parent);
            $category->setParent($dto->parent);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * Modifie une catégorie existante.
     *
     * @throws CategoryBusinessException
     */
    public function update(Category $category, UpdateCategoryDTO $dto): Category
    {
        $category->setName($dto->name);
        $category->setDescription($dto->description);
        $category->setActive($dto->active);

        if ($dto->parent !== $category->getParent()) {
            if ($dto->parent !== null) {
                $this->validateParentAssignment($category, $dto->parent);
            }
            $category->setParent($dto->parent);
        }

        $this->entityManager->flush();

        return $category;
    }

    /**
     * Supprime une catégorie si elle ne possède ni sous-catégories ni articles.
     *
     * @throws CategoryBusinessException
     */
    public function delete(Category $category): void
    {
        if (!$category->getChildren()->isEmpty()) {
            throw CategoryBusinessException::cannotDeleteCategoryWithChildren($category->getName());
        }

        if (!$category->getArticles()->isEmpty()) {
            throw CategoryBusinessException::cannotDeleteCategoryWithArticles($category->getName());
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    /**
     * Valide l'assignation d'un parent en appliquant les règles métier strictes :
     * 1. Pas d'auto-parenting.
     * 2. Pas de cycle hiérarchique avec ses descendants.
     * 3. Le parent cible ne doit pas contenir d'articles.
     *
     * @throws CategoryBusinessException
     */
    public function validateParentAssignment(Category $category, Category $parent): void
    {
        if ($category === $parent || ($category->getId() !== null && $category->getId() === $parent->getId())) {
            throw CategoryBusinessException::cannotBeOwnParent($category->getName());
        }

        // Vérification de cycle dans la chaîne ascendante du parent candidat
        $ancestor = $parent;
        while ($ancestor !== null) {
            if ($ancestor === $category || ($category->getId() !== null && $ancestor->getId() === $category->getId())) {
                throw CategoryBusinessException::cyclicHierarchy($category->getName(), $parent->getName());
            }
            $ancestor = $ancestor->getParent();
        }

        // Le parent candidat ne doit contenir aucun article
        if (!$parent->getArticles()->isEmpty()) {
            throw CategoryBusinessException::parentHasArticles($parent->getName());
        }
    }
}

