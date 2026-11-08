<?php

declare(strict_types=1);

namespace App\Exception;

class CategoryBusinessException extends BusinessException
{
    public static function cannotBeOwnParent(string $categoryName): self
    {
        return new self(sprintf('La catégorie "%s" ne peut pas être son propre parent.', $categoryName));
    }

    public static function cyclicHierarchy(string $categoryName, string $parentName): self
    {
        return new self(sprintf(
            'Impossible d\'assigner "%s" comme parent de "%s" : cela créerait une boucle hiérarchique cyclique.',
            $parentName,
            $categoryName
        ));
    }

    public static function parentHasArticles(string $parentName): self
    {
        return new self(sprintf(
            'La catégorie parente "%s" contient déjà des articles. Déplacez-les avant d\'y attacher des sous-catégories.',
            $parentName
        ));
    }

    public static function categoryHasArticlesCannotBecomeParent(string $categoryName): self
    {
        return new self(sprintf(
            'La catégorie "%s" contient des articles et ne peut pas devenir parente sans déplacement préalable de ses articles.',
            $categoryName
        ));
    }

    public static function cannotDeleteCategoryWithChildren(string $categoryName): self
    {
        return new self(sprintf('Impossible de supprimer la catégorie "%s" car elle contient des sous-catégories.', $categoryName));
    }

    public static function cannotDeleteCategoryWithArticles(string $categoryName): self
    {
        return new self(sprintf('Impossible de supprimer la catégorie "%s" car elle contient des articles.', $categoryName));
    }
}

