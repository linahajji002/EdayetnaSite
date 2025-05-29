<?php

namespace App\Entity;

use App\Repository\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_fournisseur = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column]
    private ?int $contact = null;

    /**
     * @var Collection<int, Materiaux>
     */
    #[ORM\OneToMany(targetEntity: Materiaux::class, mappedBy: 'id_fournisseur')]
    private Collection $fournisseurM;

    public function __construct()
    {
        $this->fournisseurM = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomFournisseur(): ?string
    {
        return $this->nom_fournisseur;
    }

    public function setNomFournisseur(string $nom_fournisseur): static
    {
        $this->nom_fournisseur = $nom_fournisseur;

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

    public function getContact(): ?int
    {
        return $this->contact;
    }

    public function setContact(int $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Collection<int, Materiaux>
     */
    public function getFournisseurM(): Collection
    {
        return $this->fournisseurM;
    }

    public function addFournisseurM(Materiaux $fournisseurM): static
    {
        if (!$this->fournisseurM->contains($fournisseurM)) {
            $this->fournisseurM->add($fournisseurM);
            $fournisseurM->setIdFournisseur($this);
        }

        return $this;
    }

    public function removeFournisseurM(Materiaux $fournisseurM): static
    {
        if ($this->fournisseurM->removeElement($fournisseurM)) {
            // set the owning side to null (unless already changed)
            if ($fournisseurM->getIdFournisseur() === $this) {
                $fournisseurM->setIdFournisseur(null);
            }
        }

        return $this;
    }
}
