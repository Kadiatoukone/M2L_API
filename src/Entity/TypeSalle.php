<?php

namespace App\Entity;

use App\Repository\TypeSalleRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TypeSalleRepository::class)]
#[ORM\Table(name: 'type_salle')]
class TypeSalle
{
    const CATEGORIE_SPORT     = 'sport';
    const CATEGORIE_EVENEMENT = 'evenement';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['salle:read', 'type_salle:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Groups(['salle:read', 'type_salle:read'])]
    private ?string $libelle = null;

    // Valeurs possibles : 'sport' ou 'evenement'
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Groups(['salle:read', 'type_salle:read'])]
    private ?string $categorie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }
}
