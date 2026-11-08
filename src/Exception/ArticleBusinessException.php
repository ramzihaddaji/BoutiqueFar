<?php

declare(strict_types=1);

namespace App\Exception;

class ArticleBusinessException extends BusinessException
{
    public static function categoryNotLeaf(string $categoryName): self
    {
        return new self(sprintf(
            'L\'article doit être associé à une catégorie finale (feuille). La catégorie "%s" possède des sous-catégories.',
            $categoryName
        ));
    }

    public static function categoryNotActive(string $categoryName): self
    {
        return new self(sprintf(
            'Impossible d\'associer l\'article à la catégorie "%s" car celle-ci est inactive.',
            $categoryName
        ));
    }

    public static function invalidPrice(string $price): self
    {
        return new self(sprintf('Le prix "%s" est invalide. Il doit être strictement supérieur à 0.', $price));
    }

    public static function cannotDeleteArticleWithDemandes(string $articleName): self
    {
        return new self(sprintf(
            'Impossible de supprimer l\'article "%s" car il possède des demandes clients historiques. Veuillez plutôt le désactiver.',
            $articleName
        ));
    }
}

