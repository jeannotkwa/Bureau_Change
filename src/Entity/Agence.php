<?php

namespace App\Entity;

use App\Repository\AgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AgenceRepository::class)]
#[ORM\Table(name: 'agences')]
class Agence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_agence')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_agence', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'agence est obligatoire')]
    private ?string $nomAgence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'actif';

    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'agence')]
    private Collection $utilisateurs;

    #[ORM\OneToMany(targetEntity: FondsDepart::class, mappedBy: 'agence')]
    private Collection $fondsDepart;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'agence')]
    private Collection $transactions;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->fondsDepart = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomAgence(): ?string
    {
        return $this->nomAgence;
    }

    public function setNomAgence(string $nomAgence): static
    {
        $this->nomAgence = $nomAgence;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
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

    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function getFondsDepart(): Collection
    {
        return $this->fondsDepart;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function __toString(): string
    {
        return $this->nomAgence ?? '';
    }
}
