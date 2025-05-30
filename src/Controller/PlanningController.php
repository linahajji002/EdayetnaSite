<?php

namespace App\Controller;

use App\Repository\AtelierenligneRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;

class PlanningController extends AbstractController
{
    

    #[Route('/planning', name: 'app_planning')]
    public function index(AtelierenligneRepository $atelierRepository, UserRepository $userRepository, Security $security): Response
    {
        // Récupérer l'utilisateur connecté (l'artisan)
      
        $user = $security->getUser(); // Récupérer l'utilisateur connecté

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos ateliers.');
        }

        $id_user = $user->getId(); // Récupérer l'ID de l'utilisateur connecté

        // Récupérer les ateliers de l'artisan (associés à l'utilisateur connecté)
        $ateliers = $atelierRepository->findBy(['id_user' => $id_user]);

        $events = [];
        foreach ($ateliers as $atelier) {
            // Récupérer la date de début de l'atelier
            $start = $atelier->getDatecours();
            
            // Récupérer la durée de l'atelier en minutes
            $durationInMinutes = $atelier->getDuree(); // Supposons que `duree` est la durée en minutes

            // Calculer la date de fin en ajoutant la durée en minutes
            $end = clone $start;
            $end->add(new \DateInterval('PT' . $durationInMinutes . 'M'));

            // Ajouter l'événement à la liste
            $events[] = [
                'title' => $atelier->getTitre(),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
            ];
        }
        

        // Passer les événements au frontend
        return $this->render('admin/atelier/planning/planning.html.twig', [
            'events' => json_encode($events)  // Passer les événements au frontend en format JSON
        ]);
    }
}
