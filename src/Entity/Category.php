<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // -------------------------------------------------------------------------
    // Relation récursive : catégorie parente (côté propriétaire)
    // -------------------------------------------------------------------------

    /**
     * Catégorie parente (nullable → catégorie racine si null).
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?Category $parent = null;

    // -------------------------------------------------------------------------
    // Relation récursive : sous-catégories enfants (côté inverse)
    // -------------------------------------------------------------------------

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist'], orphanRemoval: false)]
    private Collection $children;

    // -------------------------------------------------------------------------
    // Relation inverse : articles appartenant à cette catégorie
    // -------------------------------------------------------------------------

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category', cascade: ['persist'], orphanRemoval: false)]
    private Collection $articles;

    // -------------------------------------------------------------------------
    // Constructeur
    // -------------------------------------------------------------------------

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->articles = new ArrayCollection();
    }

    // -------------------------------------------------------------------------
    // Méthodes métier
    // -------------------------------------------------------------------------

    /**
     * Retourne true si la catégorie est une feuille (sans enfants).
     * Seules les catégories feuilles peuvent contenir des articles.
     */
    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    /**
     * Chemin complet depuis la racine : « Femme › Robes › Robes de soirée ».
     */
    public function getFullPath(string $separator = ' › '): string
    {
        $names = [];
        $visited = [];
        for ($c = $this; $c !== null; $c = $c->getParent()) {
            $id = spl_object_id($c);
            if (isset($visited[$id])) {
                break;
            }
            $visited[$id] = true;
            $names[] = $c->getName();
        }

        return implode($separator, array_reverse($names));
    }

    /**
     * Vérifie que la catégorie n'est pas son propre parent (protection cyclique directe).
     *
     * @throws \LogicException
     */
    private function assertNotSelfParent(Category $potentialParent): void
    {
        if ($potentialParent === $this) {
            throw new \LogicException(
                sprintf('La catégorie "%s" ne peut pas être son propre parent.', $this->name ?? 'sans nom')
            );
        }
    }

    /**
     * Vérifie qu'on ne crée pas un cycle en remontant toute la chaîne des ancêtres du parent candidat.
     *
     * @throws \LogicException
     */
    private function assertNoCyclicAncestor(Category $potentialParent): void
    {
        $ancestor = $potentialParent;

        while ($ancestor !== null) {
            if ($ancestor === $this) {
                throw new \LogicException(
                    sprintf(
                        'Impossible d\'assigner "%s" comme parent : "%s" est déjà un ancêtre de "%s".',
                        $potentialParent->getName(),
                        $this->name ?? 'sans nom',
                        $potentialParent->getName()
                    )
                );
            }
            $ancestor = $ancestor->getParent();
        }
    }

    /**
     * Vérifie qu'une catégorie qui possède des articles ne peut pas devenir parente sans déplacement préalable.
     *
     * @throws \LogicException
     */
    private function assertCanBecomeParent(): void
    {
        if (!$this->articles->isEmpty()) {
            throw new \LogicException(
                sprintf(
                    'La catégorie "%s" possède des articles. Déplacez-les avant d\'y ajouter des sous-catégories.',
                    $this->name ?? 'sans nom'
                )
            );
        }
    }

    // -------------------------------------------------------------------------
    // Getters & Setters
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Parent
    // -------------------------------------------------------------------------

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * Définit la catégorie parente en appliquant les règles métier anti-cycle.
     */
    public function setParent(?Category $parent): static
    {
        if ($parent !== null) {
            $this->assertNotSelfParent($parent);
            $this->assertNoCyclicAncestor($parent);
        }

        $this->parent = $parent;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Children
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, Category>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Ajoute une sous-catégorie en validant les règles métier.
     *
     * @throws \LogicException si la catégorie courante possède déjà des articles
     */
    public function addChild(Category $child): static
    {
        if (!$this->children->contains($child)) {
            // Une catégorie qui a des articles ne peut pas devenir parente
            $this->assertCanBecomeParent();

            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Category $child): static
    {
        if ($this->children->removeElement($child)) {
            // Désynchronise le côté propriétaire
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Articles
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    /**
     * Ajoute un article en vérifiant que la catégorie est bien une feuille.
     *
     * @throws \LogicException si la catégorie n'est pas une feuille
     */
    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            if (!$this->isLeaf()) {
                throw new \LogicException(
                    sprintf(
                        'Impossible d\'ajouter un article à la catégorie "%s" : elle possède des sous-catégories.',
                        $this->name ?? 'sans nom'
                    )
                );
            }

            $this->articles->add($article);
            $article->setCategory($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        $this->articles->removeElement($article);
        // Note : la suppression physique de l'article est gérée côté contrôleur/service,
        // pas ici, afin de respecter la stratégie de conservation des demandes historiques.

        return $this;
    }

    // -------------------------------------------------------------------------
    // __toString utilitaire
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}

