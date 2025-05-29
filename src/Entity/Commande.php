<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date de commande est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $date_commande = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le montant total est obligatoire.")]
    #[Assert\Positive(message: "Le montant total doit être un nombre positif.")]
    private ?float $montant_total = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut de la commande est obligatoire.")]
    #[Assert\Choice(choices: ["En attente", "Confirmé", "Annulé"], message: "Le statut doit être 'En attente', 'Confirmé' ou 'Annulé'.")]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: false)] // 🚀 Ensure column is NOT NULL
    #[Assert\NotBlank(message: "L'adresse de livraison est obligatoire.")]
    #[Assert\Length(min: 5, max: 255, minMessage: "L'adresse doit contenir au moins {{ limit }} caractères.")]
    private string $adresse_livraison; // 🚀 Removed `?` to ensure it's always required

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mode de paiement est obligatoire.")]
    #[Assert\Choice(choices: ["Carte Bancaire", "Espèces", "PayPal"], message: "Le mode de paiement doit être 'Carte Bancaire', 'Espèces' ou 'PayPal'.")]
    private ?string $paiement = null;


    /**
     * @var Collection<int, Lignedecommande>
     */
    #[ORM\OneToMany(targetEntity: Lignedecommande::class, mappedBy: 'id_commande')]
    private Collection $id_lignedecommande;

    /**
     * @var Collection<int, produit>
     */
    #[ORM\ManyToMany(targetEntity: Produit::class, inversedBy: 'id_commande')]
    private Collection $id_produit;

    /**
     * @var Collection<int, materiaux>
     */
    #[ORM\ManyToMany(targetEntity: Materiaux::class, inversedBy: 'id_commande')]
    private Collection $id_materiel;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commandes')]
    private ?User $id_user = null;




    public function __construct()
    {
        $this->id_lignedecommande = new ArrayCollection();
        $this->id_produit = new ArrayCollection();
        $this->id_materiel = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->date_commande;
    }

    public function setDateCommande(\DateTimeInterface $date_commande): static
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getMontantTotal(): ?float
    {
        return $this->montant_total;
    }

    public function setMontantTotal(float $montant_total): static
    {
        $this->montant_total = $montant_total;

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

    public function getAdresseLivraison(): ?string
    {
        return $this->adresse_livraison;
    }

    public function setAdresseLivraison(string $adresse_livraison): static
    {
        $this->adresse_livraison = $adresse_livraison;

        return $this;
    }

    public function getPaiement(): ?string
    {
        return $this->paiement;
    }

    public function setPaiement(string $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    /**
     * @return Collection<int, Lignedecommande>
     */
    public function getIdLignedecommande(): Collection
    {
        return $this->id_lignedecommande;
    }

    public function addIdLignedecommande(Lignedecommande $idLignedecommande): static
    {
        if (!$this->id_lignedecommande->contains($idLignedecommande)) {
            $this->id_lignedecommande->add($idLignedecommande);
            $idLignedecommande->setIdCommande($this);
        }

        return $this;
    }

    public function removeIdLignedecommande(Lignedecommande $idLignedecommande): static
    {
        if ($this->id_lignedecommande->removeElement($idLignedecommande)) {
            // set the owning side to null (unless already changed)
            if ($idLignedecommande->getIdCommande() === $this) {
                $idLignedecommande->setIdCommande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, produit>
     */
    public function getIdProduit(): Collection
    {
        return $this->id_produit;
    }

    public function addIdProduit(produit $idProduit): static
    {
        if (!$this->id_produit->contains($idProduit)) {
            $this->id_produit->add($idProduit);
        }

        return $this;
    }

    public function removeIdProduit(produit $idProduit): static
    {
        $this->id_produit->removeElement($idProduit);

        return $this;
    }

    /**
     * @return Collection<int, materiaux>
     */
    public function getIdMateriel(): Collection
    {
        return $this->id_materiel;
    }

    public function addIdMateriel(materiaux $idMateriel): static
    {
        if (!$this->id_materiel->contains($idMateriel)) {
            $this->id_materiel->add($idMateriel);
        }

        return $this;
    }

    public function removeIdMateriel(materiaux $idMateriel): static
    {
        $this->id_materiel->removeElement($idMateriel);

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
