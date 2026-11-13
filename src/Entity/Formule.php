<?php

namespace App\Entity;

use App\Repository\FormuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormuleRepository::class)]
class Formule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column]
    private int $maxPrets = 3;

    #[ORM\Column]
    private int $dureePretJours = 14;

    #[ORM\Column]
    private int $maxProlongations = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private string $penaliteParJour = '0.20';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getMaxPrets(): int
    {
        return $this->maxPrets;
    }

    public function setMaxPrets(int $maxPrets): static
    {
        $this->maxPrets = $maxPrets;

        return $this;
    }

    public function getDureePretJours(): int
    {
        return $this->dureePretJours;
    }

    public function setDureePretJours(int $dureePretJours): static
    {
        $this->dureePretJours = $dureePretJours;

        return $this;
    }

    public function getMaxProlongations(): int
    {
        return $this->maxProlongations;
    }

    public function setMaxProlongations(int $maxProlongations): static
    {
        $this->maxProlongations = $maxProlongations;

        return $this;
    }

    public function getPenaliteParJour(): string
    {
        return $this->penaliteParJour;
    }

    public function setPenaliteParJour(string $penaliteParJour): static
    {
        $this->penaliteParJour = $penaliteParJour;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }
}
