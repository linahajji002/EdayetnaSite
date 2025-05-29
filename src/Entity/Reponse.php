<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]

#[ORM\HasLifecycleCallbacks] // Ajouté pour déclencher prePersist
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_reponse = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private $finale = false; // Par défaut, une réponse n'est pas finale

    #[ORM\OneToOne(inversedBy: "reponse", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reclamation $reclamation = null;

    //  Cette méthode force la date actuelle lors de l’insertion
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->date_reponse = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->date_reponse;
    }

    public function isFinale(): bool
    {
        return $this->finale;
    }

    public function setFinale(bool $finale): self
    {
        $this->finale = $finale;
        return $this;
    }

    

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;
        return $this;
    }

}
