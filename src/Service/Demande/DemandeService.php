<?php

declare(strict_types=1);

namespace App\Service\Demande;

use App\DTO\Demande\CreateDemandeDTO;
use App\Entity\Article;
use App\Entity\Demande;
use App\Exception\DemandeBusinessException;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;

class DemandeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DemandeRepository $demandeRepository,
    ) {
    }

    /**
     * Crée et enregistre une demande de disponibilité client.
     *
     * @throws DemandeBusinessException
     */
    public function create(CreateDemandeDTO $dto): Demande
    {
        $article = $dto->article;
        $this->validateArticleForDemande($article);

        $demande = new Demande();
        $demande->setClientName($dto->clientName);
        $demande->setPhone($dto->phone);
        $demande->setCity($dto->city);
        $demande->setArticle($article);
        // La date createdAt est initialisée automatiquement dans le constructeur de Demande

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        return $demande;
    }

    /**
     * Vérifie qu'un article est éligible pour recevoir des demandes :
     * 1. L'article doit être non nul.
     * 2. L'article doit être disponible.
     * 3. La catégorie de l'article doit être active.
     *
     * @throws DemandeBusinessException
     */
    public function validateArticleForDemande(?Article $article): void
    {
        if ($article === null) {
            throw new DemandeBusinessException('Un article cible est obligatoire pour effectuer une demande.');
        }

        if (!$article->isAvailable()) {
            throw DemandeBusinessException::articleNotAvailable($article->getName());
        }

        if (!$article->getCategory()->isActive()) {
            throw DemandeBusinessException::articleCategoryInactive(
                $article->getName(),
                $article->getCategory()->getName()
            );
        }
    }
}

