<?php

namespace App\Entity;

use App\Entity\Adherent;
use App\Entity\Salles;
use App\Repository\ReservationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationsRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservations
{
    public const TYPE_PONCTUEL = 'ponctuel';
    public const TYPE_MENSUEL  = 'mensuel';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_reservation", type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: "date_debut", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\ManyToOne(targetEntity: Adherent::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: "id_adherent")]
    private ?Adherent $adherent = null;

    #[ORM\ManyToOne(targetEntity: Salles::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: "id_salles")]
    private ?Salles $salle = null;

    #[ORM\Column(name: "date_fin", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: "heure_debut", type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: "heure_fin", type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $statut = null;

    // 'ponctuel' (une fois ou une plage de dates continue) | 'mensuel' (chaque semaine)
    #[ORM\Column(name: "type_resa", type: Types::STRING, length: 20, options: ['default' => 'ponctuel'])]
    private string $typeResa = self::TYPE_PONCTUEL;

    // Renseigné automatiquement quand le statut passe à REFUSEE — permet
    // la suppression automatique après quelques jours.
    #[ORM\Column(name: "refused_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refusedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
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

    public function getSalle(): ?Salles
    {
        return $this->salle;
    }

    public function setSalle(?Salles $salle): static
    {
        $this->salle = $salle;
        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getTypeResa(): string
    {
        return $this->typeResa;
    }

    public function setTypeResa(string $typeResa): static
    {
        $this->typeResa = $typeResa;
        return $this;
    }

    public function getRefusedAt(): ?\DateTimeInterface
    {
        return $this->refusedAt;
    }

    public function setRefusedAt(?\DateTimeInterface $refusedAt): static
    {
        $this->refusedAt = $refusedAt;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'dateDebut'  => $this->dateDebut?->format('Y-m-d'),
            'dateFin'    => $this->dateFin?->format('Y-m-d'),
            'heureDebut' => $this->heureDebut?->format('H:i'),
            'heureFin'   => $this->heureFin?->format('H:i'),
            'motif'      => $this->motif,
            'statut'     => $this->statut,
            'typeResa'   => $this->typeResa,
            'refusedAt'  => $this->refusedAt?->format('Y-m-d H:i:s'),
            'salle'      => $this->salle ? [
                'id'       => $this->salle->getId(),
                'nom'      => $this->salle->getNom(),
                'adresse'  => $this->salle->getAdresse(),
                'ville'    => $this->salle->getVille(),
                'capacite' => $this->salle->getCapacite(),
            ] : null,
            'adherent' => $this->adherent ? [
                'id'      => $this->adherent->getId(),
                'nom'     => $this->adherent->getNom(),
                'prenom'  => $this->adherent->getPrenom(),
                'email'   => $this->adherent->getEmail(),
            ] : null,
        ];
    }
}
