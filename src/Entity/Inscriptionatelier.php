<?php

namespace App\Entity;

use App\Repository\InscriptionatelierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionatelierRepository::class)]
class Inscriptionatelier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateinscri = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'inscription')]
    private ?Atelierenligne $atelier = null;

    #[ORM\ManyToOne(targetEntity: User::class,inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'id_user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $id_user = null;


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getDateinscri(): ?\DateTimeInterface
    {
        return $this->dateinscri;
    }

    public function setDateinscri(\DateTimeInterface $dateinscri): static
    {
        $this->dateinscri = $dateinscri;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAtelier(): ?atelierenligne
    {
        return $this->atelier;
    }

    public function setAtelier(?atelierenligne $atelier): static
    {
        $this->atelier = $atelier;

        return $this;
    }

    public function getIdUser(): ?user
    {
        return $this->id_user;
    }

    public function setIdUser(?user $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }
}
