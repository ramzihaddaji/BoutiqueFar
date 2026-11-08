<?php

declare(strict_types=1);

namespace App\DTO\Category;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateCategoryDTO
{
    #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $name = '';

    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $description = null;

    public bool $active = true;

    /**
     * Catégorie parente facultative (null pour une catégorie racine).
     */
    public ?Category $parent = null;

    public static function fromEntity(Category $category): self
    {
        $dto = new self();
        $dto->name = $category->getName();
        $dto->description = $category->getDescription();
        $dto->active = $category->isActive();
        $dto->parent = $category->getParent();

        return $dto;
    }
}

