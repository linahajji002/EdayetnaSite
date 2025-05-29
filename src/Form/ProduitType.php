<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Promotion;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_produit', TextType::class, [
                'label' => 'Nom du Produit',
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'TND',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Disponible' => 'Disponible',
                    'Indisponible' => 'Indisponible',
                ],
            ])
            ->add('id_promotion', EntityType::class, [
                'class' => Promotion::class,
                'choice_label' => 'codeCoupon', // or any field from Promotion
                'label' => 'Promotion',
                'required' => false,
                'placeholder' => 'Aucune promotion', // optional
            ])
            // USER (id_user)
            ->add('id_user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email', // or 'username' or whichever field
                'label' => 'Utilisateur Assigné',
                'required' => false,
                'placeholder' => 'Aucun utilisateur', // optional
            ])
            ->add('image', FileType::class, [
                'label' => 'Course Image',
                'mapped' => true,
                'required' => false,
            ]) ->add('stock', IntegerType::class, [

                'label' => 'Stock',
            ]);
             $builder->get('image')->addModelTransformer(new class() implements DataTransformerInterface {
                 public function transform($value)
                 {
                     // Transform the File instance to a string (file path)
                     return null;
                 }

                 public function reverseTransform($value)
                 {
                     // Transform the string (file path) to a File instance
                     if ($value instanceof UploadedFile) {
                         return $value;
                     }

                     // Add your logic to handle the existing file path
                     // For example, create a new File instance from the file path
                     return new \Symfony\Component\HttpFoundation\File\File($value);
                 }
             });
    }

    public function transform($value)
    {
        // Transform the File instance to a string (file path)
        if ($value instanceof File) {
            return $value->getPathname();
        }

        return null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
