<?php

namespace App\Entity;

use App\Repository\DeviseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeviseRepository::class)]
#[ORM\Table(name: 'devise')]
class Devise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_devise')]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le sigle est obligatoire')]
    private ?string $sigle = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire')]
    private ?string $libelle = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $tauxAchat = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $tauxVente = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $statut = 'actif';

    #[ORM\OneToMany(targetEntity: DetailsFondsDepart::class, mappedBy: 'devise')]
    private Collection $detailsFondsDepart;

    public function __construct()
    {
        $this->detailsFondsDepart = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(string $sigle): static
    {
        $this->sigle = $sigle;
        return $this;
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

    public function getTauxAchat(): ?string
    {
        return $this->tauxAchat;
    }

    public function setTauxAchat(string $tauxAchat): static
    {
        $this->tauxAchat = $tauxAchat;
        return $this;
    }

    public function getTauxVente(): ?string
    {
        return $this->tauxVente;
    }

    public function setTauxVente(string $tauxVente): static
    {
        $this->tauxVente = $tauxVente;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDetailsFondsDepart(): Collection
    {
        return $this->detailsFondsDepart;
    }

    public function __toString(): string
    {
        return $this->sigle . ' - ' . $this->libelle;
    }
}
