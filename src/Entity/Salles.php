<?php

namespace App\Entity;

use App\Repository\SallesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SallesRepository::class)]
#[ORM\Table(name: 'salles')]
#[ORM\HasLifecycleCallbacks]
class Salles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['salle:read'])]
    private ?int $id_salles = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['salle:read'])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['salle:read'])]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['salle:read'])]
    private ?string $ville = null;


    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['salle:read'])]
    private ?int $capacite = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['salle:read'])]
    private ?string $photo = null;

    #[ORM\ManyToOne(targetEntity: TypeSalle::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['salle:read'])]
    private ?TypeSalle $typeSalle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['salle:read'])]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'salle', targetEntity: Horaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['salle:read'])]
    private Collection $horaires;

    #[ORM\ManyToOne(targetEntity: Gestionnaires::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'id_gestionnaires')]
    #[Groups(['salle:read'])]
    private ?Gestionnaires $gestionnaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['salle:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['salle:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->horaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id_salles;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getTypeSalle(): ?TypeSalle
    {
        return $this->typeSalle;
    }

    public function setTypeSalle(?TypeSalle $typeSalle): static
    {
        $this->typeSalle = $typeSalle;
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

    public function getHoraires(): Collection
    {
        return $this->horaires;
    }

    public function addHoraire(Horaire $horaire): static
    {
        if (!$this->horaires->contains($horaire)) {
            $this->horaires->add($horaire);
            $horaire->setSalle($this);
        }
        return $this;
    }

    public function removeHoraire(Horaire $horaire): static
    {
        if ($this->horaires->removeElement($horaire)) {
            if ($horaire->getSalle() === $this) {
                $horaire->setSalle(null);
            }
        }
        return $this;
    }

    public function getGestionnaire(): ?Gestionnaires
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?Gestionnaires $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id_salles,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'capacite' => $this->capacite,
            'typeSalle' => $this->typeSalle ? [
                'id' => $this->typeSalle->getId(),
                'libelle' => $this->typeSalle->getLibelle(),
                'categorie' => $this->typeSalle->getCategorie(),
            ] : null,
            'description' => $this->description,
            'photo' => $this->photo,
            'horaires' => $this->horaires->map(fn(Horaire $h) => [
                'id' => $h->getId(),
                'jour' => $h->getJour(),
                'heureOuverture' => $h->getHeureOuverture()?->format('H:i'),
                'heureFermeture' => $h->getHeureFermeture()?->format('H:i'),
                'statut' => $h->getStatut(),
            ])->toArray(),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
