<?php 
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationFormType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'L\'email ne peut pas être vide']),
                    new Callback([$this, 'validateEmailExists']),
                    new Regex([
                        'pattern' => '/^(?![.-])[A-Za-z0-9._-]+(?<![.-])@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/',
                        'message' => 'L\'Email doit être sous forme user@example.com',
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
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
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
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
            ])
            ->add('adresse', HiddenType::class, ['data' => ''])
            ->add('num_tel', HiddenType::class, ['required' => false])
            ->add('photo', HiddenType::class, ['required' => false])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le mot de passe ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex(['pattern' => '/[a-z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.']),
                    new Regex(['pattern' => '/[A-Z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.']),
                    new Regex(['pattern' => '/[0-9]/', 'message' => 'Le mot de passe doit contenir au moins un chiffre.']),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez confirmer votre mot de passe']),
                    new Callback([$this, 'validatePasswordMatch']),
                ],
            ]);
    }

    public function validateEmailExists($value, ExecutionContextInterface $context): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value]);

        if ($user) {
            $context->buildViolation('Email déjà utilisé')
                ->atPath('email')
                ->addViolation();
        }
    }

    public function validatePasswordMatch($value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $password = $form->get('password')->getData();

        if ($value !== $password) {
            $context->buildViolation('Les mots de passe ne correspondent pas.')
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
