<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'boolean')]
    private bool $available = true;

    // -------------------------------------------------------------------------
    // Relation : catégorie propriétaire (côté propriétaire ManyToOne)
    // -------------------------------------------------------------------------

    /**
     * Un article appartient obligatoirement à une catégorie finale (feuille).
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Category $category;

    // -------------------------------------------------------------------------
    // Relation inverse : demandes reçues pour cet article
    // -------------------------------------------------------------------------

    /**
     * @var Collection<int, Demande>
     */
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'article', cascade: ['persist'], orphanRemoval: false)]
    private Collection $demandes;

    // -------------------------------------------------------------------------
    // Constructeur
    // -------------------------------------------------------------------------

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
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

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Category
    // -------------------------------------------------------------------------

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * Associe l'article à une catégorie.
     * Règle métier : la catégorie doit être une feuille (sans sous-catégories).
     *
     * @throws \LogicException si la catégorie n'est pas une feuille
     */
    public function setCategory(Category $category): static
    {
        if (!$category->isLeaf()) {
            throw new \LogicException(
                sprintf(
                    'L\'article "%s" ne peut être associé qu\'à une catégorie sans sous-catégories (feuille). '
                    . '"%s" possède des sous-catégories.',
                    $this->name ?? 'sans nom',
                    $category->getName()
                )
            );
        }

        $this->category = $category;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Demandes
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setArticle($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        // On ne supprime pas automatiquement les demandes historiques.
        // La stratégie de conservation est gérée côté service/contrôleur.
        $this->demandes->removeElement($demande);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Méthodes utilitaires
    // -------------------------------------------------------------------------

    /**
     * Indique si l'article possède des demandes historiques.
     * Utilisé par les contrôleurs avant toute suppression.
     */
    public function hasDemandes(): bool
    {
        return !$this->demandes->isEmpty();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}

