<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Promotion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code_coupon', TextType::class, [
                'label' => 'Code Coupon',
                'required' => true,
            ])
            ->add('end_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => true,
            ])
            ->add('prix_nouv', MoneyType::class, [
                'label'    => 'Nouveau Prix',
                'currency' => 'TND',
                'required' => true,
            ])
            ->add('id_produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom_produit',
                'label' => 'Produits',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
    }
}