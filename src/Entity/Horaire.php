<?php

namespace App\Entity;

use App\Repository\HoraireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: HoraireRepository::class)]
#[ORM\Table(name: 'horaires')]
class Horaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['salle:read', 'horaire:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Groups(['salle:read', 'horaire:read'])]
    private ?string $jour = null; 

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['salle:read', 'horaire:read'])]
    private ?\DateTimeInterface $heureOuverture = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['salle:read', 'horaire:read'])]
    private ?\DateTimeInterface $heureFermeture = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Groups(['salle:read', 'horaire:read'])]
    private string $statut = 'ouvert';

    #[ORM\ManyToOne(targetEntity: Salles::class, inversedBy: 'horaires')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_salles')]
    private ?Salles $salle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJour(): ?string
    {
        return $this->jour;
    }

    public function setJour(string $jour): static
    {
        $this->jour = $jour;
        return $this;
    }

    public function getHeureOuverture(): ?\DateTimeInterface
    {
        return $this->heureOuverture;
    }

    public function setHeureOuverture(\DateTimeInterface $heureOuverture): static
    {
        $this->heureOuverture = $heureOuverture;
        return $this;
    }

    public function getHeureFermeture(): ?\DateTimeInterface
    {
        return $this->heureFermeture;
    }

    public function setHeureFermeture(\DateTimeInterface $heureFermeture): static
    {
        $this->heureFermeture = $heureFermeture;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getSalle(): ?Salles
    {
        return $this->salle;
    }

    public function setSalle(?Salles $salle): static
    {
        $this->salle = $salle;
        return $this;
    }
}
