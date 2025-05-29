<?php

namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $forbiddenWords = ['débile', 'stupid'];
        $pattern = '/\b(?:' . implode('|', $forbiddenWords) . ')\b/i';

        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Réponse',
                'attr' => ['class' => 'form-control'],
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La réponse ne peut pas être vide.',
                    ]),
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La réponse ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => $pattern,
                        'match' => false, 
                        'message' => 'La réponse contient des mots interdits.',
                    ]),
                ],
            ])

            ->add('finale', CheckboxType::class, [
                'label'    => 'Réponse finale',
                'required' => false, // Cela ne sera pas obligatoire
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
        ]);
        
    }
}

