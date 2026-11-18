<?php

declare(strict_types=1);

namespace App\Service\Article;

use App\DTO\Article\CreateArticleDTO;
use App\DTO\Article\UpdateArticleDTO;
use App\Entity\Article;
use App\Entity\Category;
use App\Exception\ArticleBusinessException;
use App\Repository\ArticleRepository;
use App\Service\Image\ImageStorage;
use Doctrine\ORM\EntityManagerInterface;

class ArticleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleRepository $articleRepository,
        // Optionnel : les tests unitaires existants peuvent continuer à construire le service sans lui.
        private readonly ?ImageStorage $imageStorage = null,
    ) {
    }

    /**
     * Crée un nouvel article lié à une catégorie finale active.
     *
     * @throws ArticleBusinessException
     */
    public function create(CreateArticleDTO $dto, ?string $uploadedFilename = null): Article
    {
        $this->validateCategory($dto->category);
        $this->validatePrice($dto->price);

        $article = new Article();
        $article->setName($dto->name);
        $article->setDescription($dto->description);
        $article->setPrice($dto->price);
        $article->setAvailable($dto->available);
        $article->setCategory($dto->category);

        if ($uploadedFilename !== null) {
            $article->setImage($uploadedFilename);
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    /**
     * Modifie un article existant.
     *
     * @throws ArticleBusinessException
     */
    public function update(Article $article, UpdateArticleDTO $dto, ?string $uploadedFilename = null): Article
    {
        $this->validateCategory($dto->category);
        $this->validatePrice($dto->price);

        $oldImage = $article->getImage();

        $article->setName($dto->name);
        $article->setDescription($dto->description);
        $article->setPrice($dto->price);
        $article->setAvailable($dto->available);
        $article->setCategory($dto->category);

        if ($uploadedFilename !== null) {
            $article->setImage($uploadedFilename);
        }

        $this->entityManager->flush();

        // Nouvelle image enregistrée : on retire l'ancienne du stockage
        if ($uploadedFilename !== null && $oldImage !== null && $oldImage !== $uploadedFilename) {
            $this->deleteImageSilently($oldImage);
        }

        return $article;
    }

    /**
     * Supprime un article uniquement s'il n'a aucune demande de disponibilité historique.
     *
     * @throws ArticleBusinessException
     */
    public function delete(Article $article): void
    {
        if ($article->hasDemandes()) {
            throw ArticleBusinessException::cannotDeleteArticleWithDemandes($article->getName());
        }

        $image = $article->getImage();

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        $this->deleteImageSilently($image);
    }

    /**
     * Valide l'éligibilité de la catégorie :
     * 1. Doit être non nulle.
     * 2. Doit être une catégorie feuille (sans enfants).
     * 3. Doit être active.
     *
     * @throws ArticleBusinessException
     */
    public function validateCategory(?Category $category): void
    {
        if ($category === null) {
            throw new ArticleBusinessException('Une catégorie valide est obligatoire.');
        }

        if (!$category->isLeaf()) {
            throw ArticleBusinessException::categoryNotLeaf($category->getName());
        }

        if (!$category->isActive()) {
            throw ArticleBusinessException::categoryNotActive($category->getName());
        }
    }

    /**
     * Valide la cohérence du prix.
     *
     * @throws ArticleBusinessException
     */
    public function validatePrice(?string $price): void
    {
        if ($price === null || !is_numeric($price) || (float) $price <= 0) {
            throw ArticleBusinessException::invalidPrice((string) $price);
        }
    }

    /** Un échec de suppression du fichier ne doit jamais bloquer l'opération métier. */
    private function deleteImageSilently(?string $filename): void
    {
        if ($this->imageStorage === null || $filename === null || $filename === '') {
            return;
        }

        try {
            $this->imageStorage->delete($filename);
        } catch (\Throwable) {
            // fichier orphelin possible : sans gravité
        }
    }
}