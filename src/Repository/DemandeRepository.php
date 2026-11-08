<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demande>
 */
class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    /**
     * Retourne toutes les demandes pour un article donné, avec chargement joint de l'article,
     * triées par date décroissante.
     *
     * @return Demande[]
     */
    public function findByArticle(Article $article): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.article', 'a')
            ->addSelect('a')
            ->where('d.article = :article')
            ->setParameter('article', $article)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les dernières demandes enregistrées sur l'application (pour le backoffice),
     * avec préchargement de l'article et de sa catégorie.
     *
     * @return Demande[]
     */
    public function findLatestDemandes(int $limit = 20): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.article', 'a')
            ->addSelect('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les demandes récentes (depuis une date donnée).
     *
     * @return Demande[]
     */
    public function findRecent(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.article', 'a')
            ->addSelect('a')
            ->where('d.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre de demandes groupées par article.
     *
     * @return array<array{articleId: int, articleName: string, total: int}>
     */
    public function countByArticle(): array
    {
        return $this->createQueryBuilder('d')
            ->select('a.id AS articleId, a.name AS articleName, COUNT(d.id) AS total')
            ->join('d.article', 'a')
            ->groupBy('a.id')
            ->addGroupBy('a.name')
            ->orderBy('COUNT(d.id)', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
