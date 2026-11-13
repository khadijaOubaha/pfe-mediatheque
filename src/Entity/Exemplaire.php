<?php

namespace App\Entity;

use App\Enum\EtatExemplaire;
use App\Enum\StatutExemplaire;
use App\Repository\ExemplaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExemplaireRepository::class)]
class Exemplaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $codeBarres = null;

    #[ORM\ManyToOne(inversedBy: 'exemplaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Edition $edition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $siteCourant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $siteOrigine = null;

    #[ORM\Column(length: 20, enumType: EtatExemplaire::class)]
    private EtatExemplaire $etat = EtatExemplaire::BON;

    #[ORM\Column(length: 20, enumType: StatutExemplaire::class)]
    private StatutExemplaire $statut = StatutExemplaire::DISPONIBLE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeBarres(): ?string
    {
        return $this->codeBarres;
    }

    public function setCodeBarres(string $codeBarres): static
    {
        $this->codeBarres = $codeBarres;

        return $this;
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

    public function getSiteCourant(): ?Site
    {
        return $this->siteCourant;
    }

    public function setSiteCourant(?Site $siteCourant): static
    {
        $this->siteCourant = $siteCourant;

        return $this;
    }

    public function getSiteOrigine(): ?Site
    {
        return $this->siteOrigine;
    }

    public function setSiteOrigine(?Site $siteOrigine): static
    {
        $this->siteOrigine = $siteOrigine;

        return $this;
    }

    public function getEtat(): EtatExemplaire
    {
        return $this->etat;
    }

    public function setEtat(EtatExemplaire $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getStatut(): StatutExemplaire
    {
        return $this->statut;
    }

    public function setStatut(StatutExemplaire $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->codeBarres;
    }
}
