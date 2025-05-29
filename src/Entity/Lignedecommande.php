<?php

namespace App\Entity;

use App\Repository\LignedecommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LignedecommandeRepository::class)]
class Lignedecommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column]
    private ?float $prix_unitaire = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'id_lignedecommande')]
    private ?Commande $id_commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)] // 🔥 Add this line
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $id_produit = null;


    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(float $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;
        return $this;
    }

    public function getIdCommande(): ?Commande
    {
        return $this->id_commande;
    }

    public function setIdCommande(?Commande $id_commande): static
    {
        $this->id_commande = $id_commande;
        return $this;
    }

    // ✅ ADD THESE METHODS for `id_produit`
    public function getIdProduit(): ?Produit
    {
        return $this->id_produit;
    }

    public function setIdProduit(?Produit $id_produit): static
    {
        $this->id_produit = $id_produit;
        return $this;
    }

    
}
