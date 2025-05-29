<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginAuthenticator; // 🔹 Ajout de l'authenticator manuel

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony gère automatiquement la déconnexion
    }


    // mot de passe oublié
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            // Vérifier si l'utilisateur existe
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'Aucun compte trouvé avec cet email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Générer un token unique
            $token = Uuid::v4()->toRfc4122();
            $user->setResetToken($token);
            $entityManager->flush();

            // Envoi de l'email de réinitialisation
            $emailMessage = (new Email())
                ->from('edayetna2025@gmail.com')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html(
                    "<p>Bonjour,</p>
                    <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>
                    <p><a href='http://127.0.0.1:8000/reset-password/$token'>Réinitialiser mon mot de passe</a></p>
                    <p>Si vous n'avez pas demandé cette action, ignorez cet email.</p>"
                );

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('login/forgot_password.html.twig');
    }
    // refaire un mot de passe
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword($token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifier si l'utilisateur avec ce token existe
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Lien de réinitialisation invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            
            // Hacher le nouveau mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            // Supprimer le token après réinitialisation
            $user->setResetToken(null);

            // Sauvegarde en base de données
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('login/reset_password.html.twig', ['token' => $token]);
    }

    #[Route('/process-face', name: 'process_face', methods: ['POST'])]
    public function processFace(Request $request, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, LoginAuthenticator $loginAuthenticator): JsonResponse
    {
        $image = $request->files->get('image');
        if (!$image) {
            return new JsonResponse(['success' => false, 'error' => 'Aucune image reçue']);
        }
    
        // 📌 Sauvegarde temporaire de l'image capturée
        $imagePath = 'C:/Users/Lenovo/faceID/temp.jpg';
        $image->move('C:/Users/Lenovo/faceID/', 'temp.jpg');
    
        // 📌 Exécuter le script Python
        $command = "python C:/Users/Lenovo/faceID/compare_faces.py";
        $output = shell_exec($command);
    
        // 📌 Vérifier si la sortie est valide
        $faceData = json_decode($output, true);
        if (!$faceData || !isset($faceData['success'])) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la reconnaissance faciale']);
        }
    
        // 📌 Vérifier si la reconnaissance est réussie
        if ($faceData['success'] && $faceData['is_match']) {
            // 📌 Identifier l'utilisateur d'ID 1 en base
            $user = $entityManager->getRepository(User::class)->find(1);
            if (!$user) {
                return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable']);
            }
    
            // 📌 Connecter automatiquement l'utilisateur
            $userAuthenticator->authenticateUser(
                $user,
                $loginAuthenticator,
                $request
            );
    
            // 📌 Rediriger vers /admin
            return new JsonResponse([
                'success' => true,
                'message' => 'Authentication successful',
                'user_id' => $user->getId(),
                'redirect' => '/admin'
            ]);
        }
    
        return new JsonResponse(['success' => false, 'error' => 'Face not recognized']);
    }
    
}
