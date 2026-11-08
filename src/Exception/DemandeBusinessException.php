<?php

declare(strict_types=1);

namespace App\Exception;

class DemandeBusinessException extends BusinessException
{
    public static function articleNotAvailable(string $articleName): self
    {
        return new self(sprintf(
            'L\'article "%s" n\'est actuellement pas disponible pour une demande de disponibilité.',
            $articleName
        ));
    }

    public static function articleCategoryInactive(string $articleName, string $categoryName): self
    {
        return new self(sprintf(
            'L\'article "%s" appartient à une catégorie actuellement désactivée ("%s").',
            $articleName,
            $categoryName
        ));
    }
}

