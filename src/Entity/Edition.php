<?php

namespace App\Entity;

use App\Repository\EditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditionRepository::class)]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'editions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Oeuvre $oeuvre = null;

    #[ORM\Column(length: 20, nullable: true, unique: true)]
    private ?string $isbn = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $editeur = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $anneePublication = null;

    #[ORM\Column(length: 5)]
    private string $langue = 'fr';

    #[ORM\Column(nullable: true)]
    private ?int $nbPages = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $couvertureUrl = null;

    /** @var Collection<int, Exemplaire> */
    #[ORM\OneToMany(targetEntity: Exemplaire::class, mappedBy: 'edition')]
    private Collection $exemplaires;

    /** @var Collection<int, Contribution> */
    #[ORM\OneToMany(targetEntity: Contribution::class, mappedBy: 'edition', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contributions;

    public function __construct()
    {
        $this->exemplaires = new ArrayCollection();
        $this->contributions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOeuvre(): ?Oeuvre
    {
        return $this->oeuvre;
    }

    public function setOeuvre(?Oeuvre $oeuvre): static
    {
        $this->oeuvre = $oeuvre;

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

    public function getEditeur(): ?string
    {
        return $this->editeur;
    }

    public function setEditeur(?string $editeur): static
    {
        $this->editeur = $editeur;

        return $this;
    }

    public function getAnneePublication(): ?int
    {
        return $this->anneePublication;
    }

    public function setAnneePublication(?int $anneePublication): static
    {
        $this->anneePublication = $anneePublication;

        return $this;
    }

    public function getLangue(): string
    {
        return $this->langue;
    }

    public function setLangue(string $langue): static
    {
        $this->langue = $langue;

        return $this;
    }

    public function getNbPages(): ?int
    {
        return $this->nbPages;
    }

    public function setNbPages(?int $nbPages): static
    {
        $this->nbPages = $nbPages;

        return $this;
    }

    public function getCouvertureUrl(): ?string
    {
        return $this->couvertureUrl;
    }

    public function setCouvertureUrl(?string $couvertureUrl): static
    {
        $this->couvertureUrl = $couvertureUrl;

        return $this;
    }

    /** @return Collection<int, Exemplaire> */
    public function getExemplaires(): Collection
    {
        return $this->exemplaires;
    }

    public function addExemplaire(Exemplaire $exemplaire): static
    {
        if (!$this->exemplaires->contains($exemplaire)) {
            $this->exemplaires->add($exemplaire);
            $exemplaire->setEdition($this);
        }

        return $this;
    }

    public function removeExemplaire(Exemplaire $exemplaire): static
    {
        if ($this->exemplaires->removeElement($exemplaire) && $exemplaire->getEdition() === $this) {
            $exemplaire->setEdition(null);
        }

        return $this;
    }

    /** @return Collection<int, Contribution> */
    public function getContributions(): Collection
    {
        return $this->contributions;
    }

    public function addContribution(Contribution $contribution): static
    {
        if (!$this->contributions->contains($contribution)) {
            $this->contributions->add($contribution);
            $contribution->setEdition($this);
        }

        return $this;
    }

    public function removeContribution(Contribution $contribution): static
    {
        if ($this->contributions->removeElement($contribution) && $contribution->getEdition() === $this) {
            $contribution->setEdition(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        $titre = $this->oeuvre?->getTitre() ?? 'Édition';
        $details = array_filter([$this->editeur, $this->anneePublication]);

        return $details ? sprintf('%s (%s)', $titre, implode(', ', $details)) : $titre;
    }
}
