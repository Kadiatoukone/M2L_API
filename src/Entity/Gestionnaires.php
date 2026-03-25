<?php

namespace App\Entity;

use App\Repository\GestionnairesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: GestionnairesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Gestionnaires implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['admin:read'])]
    private ?int $id_gestionnaires = null;

    #[ORM\Column(length: 255)]
    #[Groups(['admin:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['admin:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['admin:read'])]
    private ?string $identifiant = null;

    #[ORM\Column(length: 255)]
    #[Groups(['admin:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-]).{12,}$/',
        match: true,
        message: 'Votre mot de passe doit respecter les normes de sécurité de la CNIL (12 caractères, maj/min, chiffre, caractère spécial).'
    )]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['admin:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id_gestionnaires;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(string $identifiant): static
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    

    // @see UserInterface

    public function getUserIdentifier(): string
    {
        return (string) $this->identifiant;
    }

    // @see UserInterface
    
    public function getRoles(): array
    {
        $roles = $this->roles;
        // On s'assure que chaque gestionnaire possède au minimum ce rôle
        $roles[] = 'ROLE_GESTIONNAIRE';
        
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    // @see PasswordAuthenticatedUserInterface
    public function getPassword(): ?string
    { 
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    // @see UserInterface
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }
}
