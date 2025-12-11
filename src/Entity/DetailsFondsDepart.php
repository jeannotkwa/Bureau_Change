<?php

namespace App\Entity;

use App\Repository\DetailsFondsDepartRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DetailsFondsDepartRepository::class)]
#[ORM\Table(name: 'details_fonds_depart')]
class DetailsFondsDepart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_detail')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FondsDepart::class, inversedBy: 'details')]
    #[ORM\JoinColumn(name: 'id_fonds_depart', referencedColumnName: 'id_fonds_depart', nullable: false)]
    private ?FondsDepart $fondsDepart = null;

    #[ORM\ManyToOne(targetEntity: Devise::class, inversedBy: 'detailsFondsDepart')]
    #[ORM\JoinColumn(name: 'id_devise', referencedColumnName: 'id_devise', nullable: false)]
    private ?Devise $devise = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Assert\NotBlank]
    private ?string $montant = '0.00';

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(name: 'agence_id', referencedColumnName: 'id_agence', nullable: false)]
    private ?Agence $agence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFondsDepart(): ?FondsDepart
    {
        return $this->fondsDepart;
    }

    public function setFondsDepart(?FondsDepart $fondsDepart): static
    {
        $this->fondsDepart = $fondsDepart;
        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
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
}
