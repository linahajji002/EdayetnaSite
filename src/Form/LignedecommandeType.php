<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Lignedecommande;
use App\Entity\Produit;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LignedecommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['min' => 1, 'class' => 'form-control'],
                'required' => true,
            ])
            ->add('prix_unitaire', MoneyType::class, [
                'label' => 'Prix Unitaire',
                'currency' => 'TND',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('id_produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom_produit', // Display product name
                'label' => 'Produit',
                'placeholder' => 'Sélectionnez un produit',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lignedecommande::class,
        ]);
    }
}
