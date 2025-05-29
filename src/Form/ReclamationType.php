<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $forbiddenWords = ['débile', 'stupid'];
        $pattern = '/\b(?:' . implode('|', $forbiddenWords) . ')\b/i';

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre ne peut pas être vide.',
                    ]),
                    new Assert\Length([
                        'max' => 5,
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s]+$/',
                        'message' => 'Le titre ne peut contenir que des lettres, des chiffres et des espaces.',
                    ]),
                    new Assert\Regex([
                        'pattern' => $pattern,
                        'match' => false, 
                        'message' => 'Le titre contient des mots interdits.',
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description ne peut pas être vide.',
                    ]),
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    
                ],
            ]);}        

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}

