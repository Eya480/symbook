<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LivresRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\String\Slugger\SluggerInterface;

#[ORM\Entity(repositoryClass: LivresRepository::class)]
class Livres
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editeur = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEdition = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\ManyToOne(targetEntity: Categories::class, inversedBy: 'livres')]
    private ?Categories $categorie = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'categorie')]
    private Collection $livres;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'Livre')]
    private Collection $commande;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    public function __construct()
    {
        $this->livres = new ArrayCollection();
        $this->commande = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $Titre): static
    {
        $this->titre = $Titre;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $Slug): static
    {
        $this->slug = $Slug;

        return $this;
    }

    public function generateSlug(SluggerInterface $slugger): self
    {
        if (!$this->slug || $this->slug !== strtolower($this->slug)) {
            $this->slug = $slugger->slug($this->titre)->lower();
        }
        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getEditeur(): ?string
    {
        return $this->editeur;
    }

    public function setEditeur(?string $editeur): static
    {
        $this->editeur = $editeur;

        return $this;
    }

    public function getDateEdition(): ?\DateTimeInterface
    {
        return $this->dateEdition;
    }

    public function setDateEdition(?\DateTimeInterface $dateEdition): static
    {
        $this->dateEdition = $dateEdition;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }




    /**
     * Get the value of categorie
     *
     * @return ?Categories
     */
    public function getCategorie(): ?Categories
    {
        return $this->categorie;
    }

    /**
     * Set the value of categorie
     *
     * @param ?Categories $categorie
     *
     * @return self
     */
    public function setCategorie(?Categories $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getCommande(): Collection
    {
        return $this->commande;
    }

    public function addCommande(LigneCommande $commande): static
    {
        if (!$this->commande->contains($commande)) {
            $this->commande->add($commande);
            $commande->setLivre($this);
        }

        return $this;
    }

    public function removeCommande(LigneCommande $commande): static
    {
        if ($this->commande->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getLivre() === $this) {
                $commande->setLivre(null);
            }
        }

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }
}
