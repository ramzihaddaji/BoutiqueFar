<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne toutes les catégories racines (parent IS NULL).
     *
     * @return Category[]
     */
    public function findRootCategories(bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->orderBy('c.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('c.active = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne toutes les catégories actives, triées par ordre alphabétique.
     *
     * @return Category[]
     */
    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne uniquement les catégories finales (feuilles, c'est-à-dire sans aucun enfant).
     *
     * @return Category[]
     */
    public function findLeafCategories(bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.children', 'child')
            ->groupBy('c.id')
            ->having('COUNT(child.id) = 0')
            ->orderBy('c.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('c.active = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les sous-catégories directes d'une catégorie parente donnée.
     *
     * @return Category[]
     */
    public function findChildrenByParent(Category $parent, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('c.active = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère l'arbre complet des catégories racines avec préchargement (EAGER JOIN)
     * des enfants directs et des articles pour optimiser les requêtes (évite les problèmes N+1).
     *
     * @return Category[]
     */
    public function findCategoryTree(bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.children', 'children')
            ->addSelect('children')
            ->leftJoin('c.articles', 'articles')
            ->addSelect('articles')
            ->where('c.parent IS NULL')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('children.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('c.active = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Vérifie si une catégorie peut être supprimée (sans enfants ni articles associés).
     */
    public function isDeletable(Category $category): bool
    {
        return $category->getChildren()->isEmpty() && $category->getArticles()->isEmpty();
    }
}
