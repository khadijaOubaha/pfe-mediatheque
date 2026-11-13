<?php

namespace App\Entity;

use App\Enum\MotifPenalite;
use App\Repository\PenaliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PenaliteRepository::class)]
class Penalite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pret $pret = null;

    #[ORM\Column(length: 20, enumType: MotifPenalite::class)]
    private MotifPenalite $motif = MotifPenalite::RETARD;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private bool $payee = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdherent(): ?Adherent
    {
        return $this->adherent;
    }

    public function setAdherent(?Adherent $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getPret(): ?Pret
    {
        return $this->pret;
    }

    public function setPret(?Pret $pret): static
    {
        $this->pret = $pret;

        return $this;
    }

    public function getMotif(): MotifPenalite
    {
        return $this->motif;
    }

    public function setMotif(MotifPenalite $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPayee(): bool
    {
        return $this->payee;
    }

    public function setPayee(bool $payee): static
    {
        $this->payee = $payee;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Pénalité #%s', $this->id);
    }
}
