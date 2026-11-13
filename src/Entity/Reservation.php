<?php

namespace App\Entity;

use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Edition $edition = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $siteRetrait = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDemande = null;

    #[ORM\Column(length: 20, enumType: StatutReservation::class)]
    private StatutReservation $statut = StatutReservation::EN_ATTENTE;

    #[ORM\ManyToOne]
    private ?Exemplaire $exemplaireReserve = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateLimiteRetrait = null;

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

    public function getAdherent(): ?Adherent
    {
        return $this->adherent;
    }

    public function setAdherent(?Adherent $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getSiteRetrait(): ?Site
    {
        return $this->siteRetrait;
    }

    public function setSiteRetrait(?Site $siteRetrait): static
    {
        $this->siteRetrait = $siteRetrait;

        return $this;
    }

    public function getDateDemande(): ?\DateTimeImmutable
    {
        return $this->dateDemande;
    }

    public function setDateDemande(\DateTimeImmutable $dateDemande): static
    {
        $this->dateDemande = $dateDemande;

        return $this;
    }

    public function getStatut(): StatutReservation
    {
        return $this->statut;
    }

    public function setStatut(StatutReservation $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getExemplaireReserve(): ?Exemplaire
    {
        return $this->exemplaireReserve;
    }

    public function setExemplaireReserve(?Exemplaire $exemplaireReserve): static
    {
        $this->exemplaireReserve = $exemplaireReserve;

        return $this;
    }

    public function getDateLimiteRetrait(): ?\DateTimeImmutable
    {
        return $this->dateLimiteRetrait;
    }

    public function setDateLimiteRetrait(?\DateTimeImmutable $dateLimiteRetrait): static
    {
        $this->dateLimiteRetrait = $dateLimiteRetrait;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Réservation #%s', $this->id);
    }
}
