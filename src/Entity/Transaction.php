<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_transaction')]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du client est obligatoire')]
    private ?string $nom = null;

    #[ORM\ManyToOne(targetEntity: TypeIdentite::class)]
    #[ORM\JoinColumn(name: 'identite_id', referencedColumnName: 'id_identite', nullable: true)]
    private ?TypeIdentite $identite = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $adresse = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $telephone = null;

    #[ORM\Column(name: 'nature_operation', length: 20)]
    #[Assert\Choice(choices: ['achat', 'vente', 'envoi', 'reception', 'Autre'])]
    private ?string $natureOperation = null;

    #[ORM\Column(name: 'date_transaction', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateTransaction = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Agence::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'agence_id', referencedColumnName: 'id_agence', nullable: false)]
    private ?Agence $agence = null;

    #[ORM\OneToMany(targetEntity: DetailsTransaction::class, mappedBy: 'transaction', cascade: ['persist', 'remove'])]
    private Collection $details;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->details = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->dateTransaction = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
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

    public function getIdentite(): ?TypeIdentite
    {
        return $this->identite;
    }

    public function setIdentite(?TypeIdentite $identite): static
    {
        $this->identite = $identite;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getNatureOperation(): ?string
    {
        return $this->natureOperation;
    }

    public function setNatureOperation(string $natureOperation): static
    {
        $this->natureOperation = $natureOperation;
        return $this;
    }

    public function getDateTransaction(): ?\DateTimeInterface
    {
        return $this->dateTransaction;
    }

    public function setDateTransaction(\DateTimeInterface $dateTransaction): static
    {
        $this->dateTransaction = $dateTransaction;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
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

    public function addDetail(DetailsTransaction $detail): static
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setTransaction($this);
        }
        return $this;
    }

    public function removeDetail(DetailsTransaction $detail): static
    {
        if ($this->details->removeElement($detail)) {
            if ($detail->getTransaction() === $this) {
                $detail->setTransaction(null);
            }
        }
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

    public function getMontantTotal(): float
    {
        $total = 0;
        foreach ($this->details as $detail) {
            if ($this->natureOperation === 'achat') {
                $total += (float) $detail->getContreValeur();
            } else {
                $total += (float) $detail->getMontant();
            }
        }
        return $total;
    }
}
