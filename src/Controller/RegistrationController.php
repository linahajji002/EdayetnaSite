<?php 
// src/Controller/RegistrationController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager, 
        TokenStorageInterface $tokenStorage,
        HttpClientInterface $httpClient, MailerInterface $mailer
        ): Response {
    // Création d'un nouvel utilisateur
        $user = new User();

        // Création du formulaire
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            

            // Hachage du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Attribution du rôle utilisateur
            $user->setRoles(['ROLE_CLIENT']);

            // Sauvegarde de l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Connexion automatique de l'utilisateur après l'inscription
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            // Envoi de l'email de réinitialisation
                        // Envoi de l'email de bienvenue avec un design professionnel
            $emailMessage = (new Email())
            ->from('edayetna2025@gmail.com')
            ->to($user->getEmail())
            ->subject('🎉 Bienvenue sur notre plateforme !')
            ->html("
                <div style='font-family: Arial, sans-serif; text-align: center; background-color: #f4f4f4; padding: 20px;'>
                    <div style='background: white; padding: 20px; border-radius: 10px; max-width: 600px; margin: auto;'>
                        
                        <h1 style='color: #333;'>Bienvenue, {$user->getPrenom()} !</h1>
                        
                        <p style='font-size: 16px; color: #555;'>
                            Nous sommes ravis de vous accueillir sur notre plateforme. 🌟<br>
                            Découvrez des <strong>artisans talentueux</strong>, achetez des <strong>produits faits main</strong> 
                            et participez à des <strong>ateliers passionnants</strong>.
                        </p>
                        
                        <a href='http://127.0.0.1:8000/app_home' 
                            style='display: inline-block; margin: 20px auto; padding: 12px 25px; background-color: #007bff; 
                            color: white; text-decoration: none; font-size: 16px; border-radius: 5px;'>
                            Explorez la plateforme
                        </a>
                        
                       

                        <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
                        
                        <p style='font-size: 12px; color: #aaa;'>
                            &copy; " . date('Y') . " Votre Plateforme. Tous droits réservés.
                        </p>
                    </div>
                </div>
            ");

            $mailer->send($emailMessage);

            // Redirection vers la page d'accueil
            return $this->redirectToRoute('app_home');
        }

        // Rendu du formulaire d'inscription
        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
            'google_recaptcha_site_key' => $this->getParameter('google_recaptcha_site_key'),
        ]);
    }

    
}
