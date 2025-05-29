<?php

namespace App\Entity;

use App\Repository\AtelierenligneRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\User; // Assurez-vous d'importer l'entité User
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[UniqueEntity(fields: ['titre'], message: 'Ce titre est déjà utilisé.')]
#[ORM\Entity(repositoryClass: AtelierenligneRepository::class)]
class Atelierenligne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $niveau_diff = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $datecours = null;

    #[ORM\Column]
    private ?int $duree = null;


    #[ORM\Column(type: Types::TEXT)]
    private ?string $lien = null;

    
    #[ORM\OneToMany(targetEntity: Inscriptionatelier::class, mappedBy: 'atelier', cascade: ["remove"])]
    private Collection $inscription;

    #[ORM\ManyToOne(targetEntity: User::class,inversedBy: 'atelierenlignes')]
    #[ORM\JoinColumn(name: 'id_user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $id_user = null;

    public function __construct()
    {
        $this->inscription = new ArrayCollection();
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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

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

    public function getNiveauDiff(): ?string
    {
        return $this->niveau_diff;
    }

    public function setNiveauDiff(string $niveau_diff): static
    {
        $this->niveau_diff = $niveau_diff;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDatecours(): ?\DateTime
    {
        return $this->datecours;
    }

    public function setDatecours(?\DateTime $datecours): static
    {
        $this->datecours = $datecours;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(string $lien): static
    {
        $this->lien = $lien;

        return $this;
    }


    public function getInscription(): Collection
    {
        return $this->inscription;
    }

    public function addInscription(Inscriptionatelier $inscription): static
    {
        if (!$this->inscription->contains($inscription)) {
            $this->inscription->add($inscription);
            $inscription->setAtelier($this);
        }

        return $this;
    }

    public function removeInscription(Inscriptionatelier $inscription): static
    {
        if ($this->inscription->removeElement($inscription)) {
            // set the owning side to null (unless already changed)
            if ($inscription->getAtelier() === $this) {
                $inscription->setAtelier(null);
            }
        }

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->id_user;
    }

    public function setIdUser(?User $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }
}
