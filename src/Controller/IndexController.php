<?php
// fornt client 
namespace App\Controller;

use App\Entity\User;
use App\Entity\Reclamation;
use App\Repository\AtelierenligneRepository;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\MateriauxRepository;
use Knp\Component\Pager\PaginatorInterface;


class IndexController extends AbstractController
{
    /**
     * Page d'accueil
     */
    #[Route('/app_home', name: 'app_home')]
    #[IsGranted('ROLE_CLIENT')]
    public function index(MateriauxRepository $materiauxRepository, AtelierenligneRepository $atelierenligneRepository): Response
    {
        // Récupérer tous les matériaux depuis la base de données
        $materiauxes = $materiauxRepository->findAll();
        // Récupérer la date actuelle
        $today = new \DateTime();

        // Utiliser le QueryBuilder pour filtrer les ateliers dont la date de cours est après aujourd'hui
        $ateliers = $atelierenligneRepository->createQueryBuilder('a')
            ->where('a.datecours > :today') // Condition pour les ateliers dont la date de cours est après aujourd'hui
            ->setParameter('today', $today) // Passer la date actuelle en paramètre
            ->orderBy('a.datecours', 'ASC') // Trier par date de cours, du plus proche au plus lointain
            ->getQuery()
            ->getResult();

        // Limiter à 3 ateliers (les plus proches)
        $ateliers = array_slice($ateliers, 0, 3);
        // Vérifier si les données sont bien récupérées
        if (empty($materiauxes)) {
            dump("Aucun matériau trouvé !"); // Affiche un message dans la barre de debug de Symfony
        }
        return $this->render('FrontOffice/HomePage/frontuser.html.twig', [
            'materiauxes' => $materiauxes,
            'ateliers' => $ateliers, 
        ]);
    }

    /**
     * Affiche le profil de l'utilisateur avec l'ID en paramètre
     */
    #[Route('/profil/{id}', name: 'profil_page', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLIENT')]
    public function profile(int $id, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé !");
        }

        // Vérifier que l'utilisateur connecté ne consulte que son propre profil
        if ($user !== $security->getUser()) {
            throw $this->createAccessDeniedException("Accès interdit !");
        }

        return $this->render('FrontOffice/HomePage/profiluser.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Met à jour les informations du profil utilisateur
     */
    #[Route('/profil/update/{id}', name: 'profil_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLIENT')]
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
                return $this->redirectToRoute('profil_page', ['id' => $user->getId()]);
            }

            // Hacher et mettre à jour le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

        return $this->redirectToRoute('profil_page', ['id' => $user->getId()]);
    }
    #[Route('/profil/photo/update/{id}', name: 'update_photo', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLIENT')]
    public function updatePhoto(int $id, Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user || $user !== $security->getUser()) {
            throw $this->createAccessDeniedException("Vous ne pouvez modifier que votre propre photo.");
        }

        $newPhotoName = $request->request->get('photo');
        if (!$newPhotoName) {
            $this->addFlash('error', 'Veuillez entrer un nom valide pour la photo.');
            return $this->redirectToRoute('profil_page', ['id' => $user->getId()]);
        }

        $user->setPhoto($newPhotoName);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');

        return $this->redirectToRoute('profil_page', ['id' => $user->getId()]);
    }

// materiaux
    #[Route('/front/materiaux', name: 'app_front_materiaux')]
    #[IsGranted('ROLE_CLIENT')]
    public function indexfront(MateriauxRepository $materiauxRepository , PaginatorInterface $paginator,Request $request): Response
    {
        // Récupérer tous les matériaux depuis la base de données
        $materiaux = $materiauxRepository->findAll();
        $query = $materiauxRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'ASC') // Tri des matériaux par ID (ou selon vos besoins)
            ->getQuery();

        // Paginer la requête : 6 éléments par page
        $pagination = $paginator->paginate(
            $query, // La requête paginée
            $request->query->getInt('page', 1), // Page actuelle (par défaut page 1)
            6 // Nombre d'éléments par page
        );
        return $this->render('frontoffice/HomePage/materiaux.html.twig', [
            'materiauxes' => $pagination, 
            'pagination' => $pagination, 
        ]);
    }

   


    

}
