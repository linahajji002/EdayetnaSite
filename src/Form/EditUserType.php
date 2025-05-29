<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EditUserType extends AbstractType
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email ne peut pas être vide',
                    ]),
                    new Callback([$this, 'validateEmailExists']),
                    new Regex([
                        'pattern' => '/^(?![.-])[A-Za-z0-9._-]+(?<![.-])@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/',
                        'message' => 'L\'Email doit être sous forme user@example.com',
                    ]),
                    
                ],
                'label' => false,
            ])
            ->add('nom', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\'\- ]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets.',
                    ]),
                    new Regex([
                        'pattern' => '/^[^\s].*[^\s]$/',
                        'message' => 'Le nom ne doit pas commencer ni finir par un espace.',
                    ]),
                    
                ],
                'label' => false,
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\'\- ]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets.',
                    ]),
                    new Regex([
                        'pattern' => '/^[^\s].*[^\s]$/',
                        'message' => 'Le prénom ne doit pas commencer ni finir par un espace.',
                    ]),
                    
                ],
                'label' => false,
            ])
            ->add('adresse', TextType::class, [
                'data' => '',
                'label' => false,
                'attr' => [
                    'placeholder' => 'Champ vide',
                ],
            ])
            ->add('num_tel', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Champ vide',
                ],
            ])
            ->add('photo', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Champ vide',
                ],
            ])
            // Ajout du champ pour le rôle
            ->add('roles', ChoiceType::class, [
                'choices'  => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Client'         => 'ROLE_CLIENT',
                    'Artisan'        => 'ROLE_ARTISAN',
                ],
                'label' => false,
                'multiple' => true,   // Le champ retourne un tableau de valeurs
                'expanded' => false,  // false pour une liste déroulante, true pour des cases à cocher
            ])
            // Ajout du champ pour l'ancien mot de passe
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'constraints' => [
                    new Callback([$this, 'validateCurrentPassword']),
                ],
            ])
            // Ajout du champ pour le nouveau mot de passe
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le nouveau mot de passe doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nouveau mot de passe ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Le nouveau mot de passe doit contenir au moins une lettre majuscule.',
                    ]),
                    new Regex([
                        'pattern' => '/[a-z]/',
                        'message' => 'Le nouveau mot de passe doit contenir au moins une lettre minuscule.',
                    ]),
                    new Regex([
                        'pattern' => '/[0-9]/',
                        'message' => 'Le nouveau mot de passe doit contenir au moins un chiffre.',
                    ]),
                ],
            ])
        ;
    }

    public function validateEmailExists($value, ExecutionContextInterface $context): void
    {
        $user = $context->getRoot()->getData();
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value]);
    
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $context->buildViolation('Cet email est déjà utilisé par un autre utilisateur.')
                ->atPath('email')
                ->addViolation();
        }
    }
    
    /**
     * Valide que l'ancien mot de passe saisi correspond bien au mot de passe stocké (haché)
     */
    public function validateCurrentPassword($value, ExecutionContextInterface $context): void
    {
        $user = $context->getRoot()->getData();
    
        if ($value) { // Vérifie que l'utilisateur a saisi un ancien mot de passe
            if (!$this->passwordHasher->isPasswordValid($user, $value)) {
                $context->buildViolation('L\'ancien mot de passe est incorrect.')
                    ->addViolation();
            }
        }
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
