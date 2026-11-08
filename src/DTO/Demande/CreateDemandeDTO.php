<?php

declare(strict_types=1);

namespace App\DTO\Demande;

use App\Entity\Article;
use Symfony\Component\Validator\Constraints as Assert;

class CreateDemandeDTO
{
    #[Assert\NotBlank(message: 'Votre nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $clientName = '';

    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: 'Le numéro de téléphone doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9+() \-\.]{8,20}$/',
        message: 'Le numéro de téléphone contient des caractères invalides.'
    )]
    public string $phone = '';

    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La ville doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $city = '';

    #[Assert\NotNull(message: 'L\'article concerné par la demande est requis.')]
    public ?Article $article = null;
}

