<?php

namespace App\Entity;

use App\Repository\DetailsTransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DetailsTransactionRepository::class)]
#[ORM\Table(name: 'details_transaction')]
class DetailsTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_detail')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Transaction::class, inversedBy: 'details')]
    #[ORM\JoinColumn(name: 'id_transaction', referencedColumnName: 'id_transaction', nullable: false)]
    private ?Transaction $transaction = null;

    #[ORM\ManyToOne(targetEntity: Devise::class)]
    #[ORM\JoinColumn(name: 'devise_id_input', referencedColumnName: 'id_devise', nullable: false)]
    private ?Devise $deviseInput = null;

    #[ORM\ManyToOne(targetEntity: Devise::class)]
    #[ORM\JoinColumn(name: 'devise_id_output', referencedColumnName: 'id_devise', nullable: false)]
    private ?Devise $deviseOutput = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $montant = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $taux = null;

    #[ORM\Column(name: 'contre_valeur', type: 'decimal', precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $contreValeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function getDeviseInput(): ?Devise
    {
        return $this->deviseInput;
    }

    public function setDeviseInput(?Devise $deviseInput): static
    {
        $this->deviseInput = $deviseInput;
        return $this;
    }

    public function getDeviseOutput(): ?Devise
    {
        return $this->deviseOutput;
    }

    public function setDeviseOutput(?Devise $deviseOutput): static
    {
        $this->deviseOutput = $deviseOutput;
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

    public function getTaux(): ?string
    {
        return $this->taux;
    }

    public function setTaux(string $taux): static
    {
        $this->taux = $taux;
        return $this;
    }

    public function getContreValeur(): ?string
    {
        return $this->contreValeur;
    }

    public function setContreValeur(string $contreValeur): static
    {
        $this->contreValeur = $contreValeur;
        return $this;
    }
}
