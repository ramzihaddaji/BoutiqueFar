<?php

declare(strict_types=1);

namespace App\DTO\Article;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateArticleDTO
{
    #[Assert\NotBlank(message: 'Le nom de l\'article est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $name = '';

    #[Assert\Length(
        max: 10000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être strictement supérieur à 0.')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'Le format du prix est invalide (ex: 19.99).'
    )]
    public ?string $price = null;

    #[Assert\File(
        maxSize: '5M',
        mimeTypes: [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
        maxSizeMessage: 'L\'image ne doit pas dépasser 5 Mo.',
        mimeTypesMessage: 'Format d\'image invalide (seuls JPEG, PNG et WEBP sont acceptés).'
    )]
    public ?UploadedFile $imageFile = null;

    /**
     * Conserve le nom du fichier image existant.
     */
    public ?string $existingImage = null;

    public bool $available = true;

    #[Assert\NotNull(message: 'Veuillez associer une catégorie à l\'article.')]
    public ?Category $category = null;

    public static function fromEntity(Article $article): self
    {
        $dto = new self();
        $dto->name = $article->getName();
        $dto->description = $article->getDescription();
        $dto->price = $article->getPrice();
        $dto->existingImage = $article->getImage();
        $dto->available = $article->isAvailable();
        $dto->category = $article->getCategory();

        return $dto;
    }
}

