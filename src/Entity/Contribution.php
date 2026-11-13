<?php

namespace App\Entity;

use App\Enum\RoleContribution;
use App\Repository\ContributionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContributionRepository::class)]
class Contribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contributions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Edition $edition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personne $personne = null;

    #[ORM\Column(length: 20, enumType: RoleContribution::class)]
    private RoleContribution $role = RoleContribution::AUTEUR;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    public function getPersonne(): ?Personne
    {
        return $this->personne;
    }

    public function setPersonne(?Personne $personne): static
    {
        $this->personne = $personne;

        return $this;
    }

    public function getRole(): RoleContribution
    {
        return $this->role;
    }

    public function setRole(RoleContribution $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->personne, $this->role->value);
    }
}
