<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]

#[ORM\HasLifecycleCallbacks] // Ajouté pour déclencher prePersist
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING , length: 255 )]
    private $statut = 'En attente';

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateReclamation = null;

    #[ORM\ManyToOne(targetEntity: User::class , inversedBy: 'reclamation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: "reclamation", cascade: ["persist", "remove"])]
    private ?Reponse $reponse = null;

    //  Cette méthode force la date actuelle lors de l’insertion
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->dateReclamation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function updateStatutBasedOnReponse(): void
    {
        if ($this->getReponse()) {
            // Vérifie si la réponse est finale
            $this->statut = $this->getReponse()->isFinale() ? 'Répondue' : 'En cours';
            
        } else {
            // Si aucune réponse, maintenir le statut par défaut
            $this->statut = 'En attente';
        }
        
    }

    public function getDateReclamation(): ?\DateTimeInterface
    {
        return $this->dateReclamation;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getReponse(): ?Reponse
    {
        return $this->reponse;
    }

    public function setReponse(?Reponse $reponse): static
    {
        $this->reponse = $reponse;
        return $this;
    }
}
