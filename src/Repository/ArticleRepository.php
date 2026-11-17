<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Retourne tous les articles disponibles pour le catalogue public.
     * Prend en compte la disponibilité de l'article et le statut actif de sa catégorie.
     * Effectue une jointure optimisée avec la catégorie pour éviter les requêtes N+1.
     *
     * @return Article[]
     */
    public function findAvailableArticles(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.available = :available')
            ->andWhere('c.active = :categoryActive')
            ->setParameter('available', true)
            ->setParameter('categoryActive', true)
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias de commodité pour la compatibilité existante.
     *
     * @return Article[]
     */
    public function findAvailable(): array
    {
        return $this->findAvailableArticles();
    }

    /**
     * Retourne un article disponible par son identifiant unique.
     * Assure que l'article est disponible et que sa catégorie est active.
     */
    public function findAvailableById(int $id): ?Article
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.id = :id')
            ->andWhere('a.available = :available')
            ->andWhere('c.active = :categoryActive')
            ->setParameter('id', $id)
            ->setParameter('available', true)
            ->setParameter('categoryActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les articles d'une catégorie donnée.
     * Optionnellement filtré sur la disponibilité pour l'affichage catalogue.
     *
     * @return Article[]
     */
    public function findByCategory(Category $category, bool $availableOnly = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.name', 'ASC');

        if ($availableOnly) {
            $qb->andWhere('a.available = :available')
                ->andWhere('c.active = :categoryActive')
                ->setParameter('available', true)
                ->setParameter('categoryActive', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les articles actifs et en stock pour la vitrine plein écran de l'accueil,
     * ordonnés du plus récent au plus ancien, avec jointure eager de la catégorie
     * pour prévenir tout problème de requêtes N+1.
     *
     * @return Article[]
     */
    public function findInStockForShowcase(int $limit = 12): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.available = :available')
            ->andWhere('c.active = :categoryActive')
            ->setParameter('available', true)
            ->setParameter('categoryActive', true)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche textuelle d'articles par mot-clé (dans le nom ou la description).
     *
     * @return Article[]
     */
    public function searchArticles(string $query, bool $availableOnly = true): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.name LIKE :query OR a.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.name', 'ASC');

        if ($availableOnly) {
            $qb->andWhere('a.available = :available')
                ->andWhere('c.active = :categoryActive')
                ->setParameter('available', true)
                ->setParameter('categoryActive', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les derniers articles enregistrés ou mis à disposition.
     *
     * @return Article[]
     */
    public function findLatestArticles(int $limit = 10, bool $availableOnly = true): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit);

        if ($availableOnly) {
            $qb->where('a.available = :available')
                ->andWhere('c.active = :categoryActive')
                ->setParameter('available', true)
                ->setParameter('categoryActive', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les articles populaires (avec le plus grand nombre de demandes de disponibilité),
     * actifs et disponibles pour le catalogue public.
     *
     * @return Article[]
     */
    public function findPopularArticles(int $limit = 6): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->leftJoin('a.demandes', 'd')
            ->where('a.available = :available')
            ->andWhere('c.active = :categoryActive')
            ->setParameter('available', true)
            ->setParameter('categoryActive', true)
            ->groupBy('a.id')
            ->addGroupBy('c.id')
            ->orderBy('COUNT(d.id)', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Pagination native avec Doctrine Paginator pour le catalogue public,
     * supportant optionnellement la recherche textuelle et le filtrage par catégorie.
     *
     * @return Paginator<Article>
     */
    public function paginatePublicArticles(int $page = 1, int $limit = 12, ?Category $category = null, ?string $search = null): Paginator
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->addSelect('c')
            ->where('a.available = :available')
            ->andWhere('c.active = :categoryActive')
            ->setParameter('available', true)
            ->setParameter('categoryActive', true)
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($category !== null) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $category);
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('a.name LIKE :search OR a.description LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * Vérifie si un article peut être supprimé (ne possède aucune demande associée).
     */
    public function isDeletable(Article $article): bool
    {
        return !$article->hasDemandes();
    }

    /**
     * Retourne les articles ayant des demandes (utilisé pour le backoffice).
     *
     * @return Article[]
     */
    public function findWithDemandes(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.demandes', 'd')
            ->addSelect('d')
            ->groupBy('a.id')
            ->having('COUNT(d.id) > 0')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
