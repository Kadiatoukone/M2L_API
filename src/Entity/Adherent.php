<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\AdherentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdherentRepository::class)]
#[ORM\Table(name: 'adherent')]
#[ORM\HasLifecycleCallbacks]
class Adherent implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_adherent = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    private string $numero_adherent;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nom;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $prenom;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $mot_de_passe;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $ligue;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $poste;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id_adherent;
    }

    public function getNumeroAdherent(): string
    {
        return $this->numero_adherent;
    }

    public function setNumeroAdherent(string $numero_adherent): static
    {
        $this->numero_adherent = $numero_adherent;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): static
    {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    public function getLigue(): string
    {
        return $this->ligue;
    }

    public function setLigue(string $ligue): static
    {
        $this->ligue = $ligue;
        return $this;
    }

    public function getPoste(): string
    {
        return $this->poste;
    }

    public function setPoste(string $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    // ── UserInterface ──────────────────────────────────────────────────────────

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function eraseCredentials(): void {}

    // ── Lifecycle ──────────────────────────────────────────────────────────────

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
            'id'              => $this->id_adherent,
            'numero_adherent' => $this->numero_adherent,
            'nom'             => $this->nom,
            'prenom'          => $this->prenom,
            'email'           => $this->email,
            'ligue'           => $this->ligue,
            'poste'           => $this->poste,
            'createdAt'       => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt'       => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
