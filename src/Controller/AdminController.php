<?php 
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager,PaginatorInterface $paginator,Request $request): Response
    {
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
            // Vérifie si l'utilisateur a le rôle ROLE_ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('admin/baseback.html.twig',[
                'users' => $users,
                'totalUsers' => $totalUsers,
                'statutCount' => json_encode($statutCount),
                'statutPercentages' => json_encode($statutPercentages),
                'totalArtisans' => $totalArtisans
            ]);
        }
        
        // Vérifie si l'utilisateur a le rôle ROLE_ARTISAN
        if ($this->isGranted('ROLE_ARTISAN')) {
            return $this->render('artisan/baseback.html.twig');
        }

        // Si l'utilisateur n'a aucun des rôles nécessaires, accès refusé
        throw $this->createAccessDeniedException('Accès interdit');
    }

   
    
}
