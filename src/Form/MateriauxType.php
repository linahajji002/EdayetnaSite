<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Fournisseur;
use App\Entity\Materiaux;
use App\Entity\User;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class MateriauxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom_materiel', TextType::class, [
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le nom du matériel est obligatoire.']),
                new Assert\Regex([
                    'pattern' => '/^[a-zA-ZÀ-ÿ\s]+$/',
                    'message' => 'Le nom du matériel doit contenir uniquement des lettres et des espaces.'
                ]),
            ],
            'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Marteau']
        ])

        ->add('quantite_stock', IntegerType::class, [
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(['message' => 'La quantité en stock est obligatoire.']),
                new Assert\GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'La quantité en stock doit être supérieure ou égale à 1.'
                ]),
                new Assert\Regex([
                    'pattern' => '/^\d+$/',  // Expression régulière pour les entiers (numéros positifs)
                    'message' => 'La quantité en stock doit être un nombre entier.'
                ]),
            ],
            'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 10']
        ])
        
        ->add('seuil_min', IntegerType::class, [
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le seuil minimum est obligatoire.']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 3,
                    'notInRangeMessage' => 'Le seuil minimum doit être compris entre {{ min }} et {{ max }}.',
                ]),
                new Assert\Regex([
                    'pattern' => '/^\d+$/',  
                    'message' => 'Le seuil minimum doit être un nombre entier.'
                ]),
            ],
            'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 2']
        ])
        

        ->add('prix_unitaire', NumberType::class, [
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le prix unitaire est obligatoire.']),
                new Assert\Positive([
                    'message' => 'Le prix unitaire doit être supérieur à 0.'
                ]),
                new Assert\Type([
                    'type' => 'float',
                    'message' => 'Le prix unitaire doit être un nombre décimal valide en dinar tunisien.'
                ]),
            ],
            'html5' => true, 
            'scale' => 2, 
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Ex: 20.50',
                'step' => '0.01'  // Permet d'entrer des décimales dans l'input HTML
            ]
        ])
        ->add('categorie', ChoiceType::class, [
            'choices' => [
                'Peinture' => 'peinture',
                'Poterie' => 'poterie',
                'Céramique' => 'ceramique',
                'Bois Sculpté' => 'bois',
                'Tissu et Textile' => 'tissu',
                'Métal Forgé' => 'metal',
                'Papier et Carton' => 'papier',
                'Verre Soufflé' => 'verre',
                'Cuir' => 'cuir',
                'Pierre et Marbre' => 'pierre',
            ],
            'placeholder' => 'Sélectionner une catégorie',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new Assert\NotBlank(['message' => 'La catégorie est obligatoire.']),
            ],
        ])
        
        
        ->add('description', TextType::class, [
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                new Assert\Length([
                    'min' => 10,
                    'max' => 500,
                    'minMessage' => 'La description doit contenir au moins 10 caractères.',
                    'maxMessage' => 'La description ne doit pas dépasser 500 caractères.'
                ]),
            ],
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Décrivez le matériel...',
                'rows' => 4
            ]
        ])
        ->add('photo', TextType::class, [
            'required' => false,  
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le chemin de la photo est obligatoire.']),
               
            ],
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Entrez le chemin ou l\'URL de la photo'
            ]
        ])
        ->add('id_fournisseur', EntityType::class, [
            'class' => Fournisseur::class,
            'choice_label' => 'nom_fournisseur',  // Spécifie la propriété à afficher pour chaque fournisseur
            'placeholder' => 'Sélectionner un fournisseur',  // Optionnel
            'attr' => ['class' => 'form-control'],
            'required' => true,  
        ]);
        
    
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Materiaux::class,
        ]);
    }
}
