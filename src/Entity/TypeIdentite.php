<?php

namespace App\Entity;

use App\Repository\TypeIdentiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TypeIdentiteRepository::class)]
#[ORM\Table(name: 'types_identite')]
class TypeIdentite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_identite')]
    private ?int $id = null;

    #[ORM\Column(name: 'libelle_identite', length: 100)]
    #[Assert\NotBlank]
    private ?string $libelleIdentite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleIdentite(): ?string
    {
        return $this->libelleIdentite;
    }

    public function setLibelleIdentite(string $libelleIdentite): static
    {
        $this->libelleIdentite = $libelleIdentite;
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelleIdentite ?? '';
    }
}
