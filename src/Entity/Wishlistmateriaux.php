<?php

namespace App\Entity;

use App\Repository\WishlistmateriauxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WishlistmateriauxRepository::class)]
class Wishlistmateriaux
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?User $user = null;

    /**
     * @var Collection<int, Materiaux>
     */
    #[ORM\ManyToMany(targetEntity: Materiaux::class, inversedBy: 'wishlistmateriauxes')]
    private Collection $id_materiel;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_ajout = null;


    public function __construct()
    {
        $this->id_materiel = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $id_user): static
    {
        $this->user = $id_user;

        return $this;
    }

    /**
     * @return Collection<int, Materiaux>
     */
    public function getIdMateriel(): Collection
    {
        return $this->id_materiel;
    }

    public function addIdMateriel(Materiaux $idMateriel): static
    {
        if (!$this->id_materiel->contains($idMateriel)) {
            $this->id_materiel->add($idMateriel);
        }

        return $this;
    }

    public function removeIdMateriel(Materiaux $idMateriel): static
    {
        $this->id_materiel->removeElement($idMateriel);

        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(\DateTimeInterface $date_ajout): static
    {
        $this->date_ajout = $date_ajout;

        return $this;
    }

}
