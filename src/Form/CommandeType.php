<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse de Livraison',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('paiement', ChoiceType::class, [
                'label' => 'Méthode de Paiement',
                'choices' => [
                    'Carte Bancaire' => 'Carte Bancaire',
                    'Espèces' => 'Espèces',
                    'PayPal' => 'PayPal',
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('montantTotal', MoneyType::class, [
                'label' => 'Montant Total (TND)',
                'currency' => 'TND',
                'attr' => ['class' => 'form-control bg-light', 'readonly' => 'readonly'],
                'required' => true,
            ])
            ->add('dateCommande', DateType::class, [
                'label' => 'Date de Commande',
                'widget' => 'single_text', // Allows inline date picker
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'En attente',
                    'Confirmé' => 'Confirmé',
                    'Annulé' => 'Annulé',
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-success'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
