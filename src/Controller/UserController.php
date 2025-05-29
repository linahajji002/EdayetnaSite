<?php 
// src/Controller/UserController.php
// backoffice user
namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;




class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users')]
public function index(
    EntityManagerInterface $entityManager,
    PaginatorInterface $paginator,
    Request $request
): Response {
    // Récupérer tous les utilisateurs avec pagination
    $query = $entityManager->createQuery('SELECT u FROM App\Entity\User u');
    $users = $paginator->paginate($query, $request->query->getInt('page', 1), 5);

    // Nombre total d'utilisateurs
    $totalUsers = $entityManager->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')
        ->getSingleScalarResult();

    // Nombre d'utilisateurs par statut (active, blocked)
    $query = $entityManager->createQuery("
        SELECT COUNT(u.id) as count, u.statut
        FROM App\Entity\User u
        GROUP BY u.statut
    ");
    $statutResults = $query->getResult();

    // Initialisation des compteurs
    $statutCount = [
        'active' => 0,
        'blocked' => 0
    ];

    // Stocker les valeurs récupérées
    foreach ($statutResults as $row) {
        $statutCount[$row['statut']] = $row['count'];
    }

    // Calculer les pourcentages
    $statutPercentages = [];
    if ($totalUsers > 0) {
        foreach ($statutCount as $statut => $count) {
            $statutPercentages[$statut] = round(($count / $totalUsers) * 100, 2);
        }
    } else {
        $statutPercentages = array_fill_keys(array_keys($statutCount), 0);
    }

    // Nombre total d'artisans (Recherche du rôle dans la colonne JSON)
    $query = $entityManager->createQuery("
        SELECT COUNT(u.id) as total_artisans 
        FROM App\Entity\User u 
        WHERE u.roles LIKE :role
    ")->setParameter('role', '%ROLE_ARTISAN%');

    $result = $query->getScalarResult();
    $totalArtisans = $result[0]['total_artisans'] ?? 0; // Évite une erreur si vide

    return $this->render('user/index.html.twig', [
        'users' => $users,
        'totalUsers' => $totalUsers,
        'statutCount' => json_encode($statutCount), // Nombre total d'utilisateurs par statut
        'statutPercentages' => json_encode($statutPercentages), // Pourcentages des statuts
        'totalArtisans' => $totalArtisans // Garde le nombre total d'artisans
    ]);
}

    
    // modifier
    #[Route('/edituser/{id}', name: 'edit_user', methods: ['POST'])]
    public function editUser(
        int $id, 
        Request $request, 
        UserRepository $userRepository, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response 
    {
        $user = $userRepository->find($id);
    
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé.'], 404);
        }
    
        // Mise à jour des informations utilisateur
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        $user->setAdresse($request->request->get('adresse'));
        $user->setNumTel($request->request->get('numTel'));
        $user->setPhoto($request->request->get('photo'));
        
        // Mise à jour du rôle
        $newRole = $request->request->get('role');
        if (in_array($newRole, ['ROLE_CLIENT', 'ROLE_ADMIN', 'ROLE_ARTISAN'])) {
            $user->setRoles([$newRole]);
        }

        // 🔹 Mise à jour du statut (active/blocked)
        $newStatus = $request->request->get('statut');
        if (in_array($newStatus, ['active', 'blocked'])) {
            $user->setStatut($newStatus);
        }

        // Gestion du mot de passe
        $currentPassword = $request->request->get('currentPassword');
        $newPassword = $request->request->get('newPassword');
    
        if (!empty($currentPassword) && !empty($newPassword)) {
            // Vérifier que le mot de passe actuel est correct
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 400);
            }
    
            // Hacher le nouveau mot de passe et l'enregistrer
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }
    
        // Sauvegarde des modifications
        $entityManager->flush();
    
        return $this->json(['success' => true, 'message' => 'Utilisateur mis à jour avec succès !']);
    }
    

    #[Route('/deleteuser/{id}', name: 'deleteuser')]
    public function deleteauth(ManagerRegistry $doctrine, $id): Response
    {
        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Author not found');
        }

        $em = $doctrine->getManager();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully!');

        return $this->redirectToRoute('app_users');
    }   
    // #[Route('/send-test-email', name: 'send_test_email')]
    // public function sendTestEmail(MailerInterface $mailer): Response
    // {
    //     $email = (new Email())
    //         ->from('edayetna@gmail.com')
    //         ->to('nouha.hadjbrahim@esprit.tn')
    //         ->subject('Test Symfony Mailer')
    //         ->text('Ceci est un test d\'envoi d\'email avec Gmail et Symfony.');

    //     $mailer->send($email);

    //     return new Response('Email envoyé avec succès !');
    // }
    #[Route('/profiluser/{id}', name: 'profil_user_back', requirements: ['id' => '\d+'])]
    public function profile(Security $security): Response
    {
        $user = $security->getUser(); // Récupérer l'utilisateur connecté


        return $this->render('admin/profiluserback.html.twig', [
            'user' => $user
        ]);
    }
    #[Route('/profiluser/update/{id}', name: 'profil_user_back_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateProfile(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user || $user !== $security->getUser()) {
            throw $this->createAccessDeniedException("Vous ne pouvez modifier que votre propre profil.");
        }

        // Mise à jour des champs standards
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        $user->setAdresse($request->request->get('adresse'));
        $user->setNumTel($request->request->get('numTel'));

        // Gestion du changement de mot de passe
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');

        if ($currentPassword && $newPassword) {
            // Vérifier si l'ancien mot de passe est correct
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('profil_user_back', ['id' => $user->getId()]);
            }

            // Hacher et mettre à jour le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

        return $this->redirectToRoute('profil_user_back', ['id' => $user->getId()]);
    }
    #[Route('/profiluser/photo/update/{id}', name: 'update_back_photo', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updatePhoto(int $id, Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user || $user !== $security->getUser()) {
            throw $this->createAccessDeniedException("Vous ne pouvez modifier que votre propre photo.");
        }

        $newPhotoName = $request->request->get('photo');
        if (!$newPhotoName) {
            $this->addFlash('error', 'Veuillez entrer un nom valide pour la photo.');
            return $this->redirectToRoute('profil_user_back', ['id' => $user->getId()]);
        }

        $user->setPhoto($newPhotoName);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');

        return $this->redirectToRoute('profil_user_back', ['id' => $user->getId()]);
    }

    // artisan profil back
    #[Route('/profilartisan/{id}', name: 'profil_artisan_back', requirements: ['id' => '\d+'])]
    public function profilartisan(Security $security): Response
    {
        $user = $security->getUser(); // Récupérer l'utilisateur connecté


        return $this->render('artisan/profilartisan.html.twig', [
            'user' => $user
        ]);
    }
    #[Route('/search-users', name: 'search_users', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
{
    $query = $request->query->get('query', '');

    $users = $userRepository->createQueryBuilder('u')
        ->where('u.nom LIKE :query')
        ->orWhere('u.prenom LIKE :query')
        ->orWhere('u.email LIKE :query')
        ->setParameter('query', "%{$query}%")
        ->getQuery()
        ->getResult();

    $baseUrl = $request->getSchemeAndHttpHost(); // Récupère automatiquement l'URL de base

    $usersArray = array_map(fn($user) => [
        'id' => $user->getId(),
        'nom' => $user->getNom(),
        'prenom' => $user->getPrenom(),
        'email' => $user->getEmail(),
        'statut' => $user->getStatut(),
        'photo' => $baseUrl . '/build/assets/images/' . ($user->getPhoto() ?: 'userbb.jpeg'),
    ], $users);

    return new JsonResponse($usersArray);
}

}
