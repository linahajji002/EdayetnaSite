<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le code de promotion est obligatoire.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Le code doit avoir au moins 3 caractères.")]
    private ?string $code_coupon = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: "start_date", message: "La date de fin doit être après la date de début.")]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix promotionnel est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif.")]
    private ?float $prix_nouv = null;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'id_promotion')]
    private Collection $id_produit;

    public function __construct()
    {
        $this->id_produit = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeCoupon(): ?string
    {
        return $this->code_coupon;
    }

    public function setCodeCoupon(string $code_coupon): static
    {
        $this->code_coupon = $code_coupon;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }
    public function isActive(): bool
    {
        $today = new \DateTime();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;
        return $this;
    }

    public function getPrixNouv(): ?float
    {
        return $this->prix_nouv;
    }

    public function setPrixNouv(float $prix_nouv): static
    {
        $this->prix_nouv = $prix_nouv;
        return $this;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getIdProduit(): Collection
    {
        return $this->id_produit;
    }

    public function addIdProduit(Produit $idProduit): static
    {
        if (!$this->id_produit->contains($idProduit)) {
            $this->id_produit->add($idProduit);
            $idProduit->setIdPromotion($this);
        }

        return $this;
    }

    public function removeIdProduit(Produit $idProduit): static
    {
        if ($this->id_produit->removeElement($idProduit)) {
            // set the owning side to null (unless already changed)
            if ($idProduit->getIdPromotion() === $this) {
                $idProduit->setIdPromotion(null);
            }
        }

        return $this;
    }
}
