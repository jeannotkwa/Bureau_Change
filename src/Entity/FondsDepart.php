<?php

namespace App\Entity;

use App\Repository\FondsDepartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FondsDepartRepository::class)]
#[ORM\Table(name: 'fonds_depart')]
class FondsDepart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_fonds_depart')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_jour', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateJour = null;

    #[ORM\ManyToOne(targetEntity: Agence::class, inversedBy: 'fondsDepart')]
    #[ORM\JoinColumn(name: 'agence_id', referencedColumnName: 'id_agence', nullable: false)]
    private ?Agence $agence = null;

    #[ORM\OneToMany(targetEntity: DetailsFondsDepart::class, mappedBy: 'fondsDepart', cascade: ['persist', 'remove'])]
    private Collection $details;

    #[ORM\Column(length: 20)]
    private string $statut = 'ouvert';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->details = new ArrayCollection();
        $this->dateJour = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateJour(): ?\DateTimeInterface
    {
        return $this->dateJour;
    }

    public function setDateJour(\DateTimeInterface $dateJour): static
    {
        $this->dateJour = $dateJour;
        return $this;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;
        return $this;
    }

    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(DetailsFondsDepart $detail): static
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setFondsDepart($this);
        }
        return $this;
    }

    public function removeDetail(DetailsFondsDepart $detail): static
    {
        if ($this->details->removeElement($detail)) {
            if ($detail->getFondsDepart() === $this) {
                $detail->setFondsDepart(null);
            }
        }
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
