<?php

namespace App\Controller;

use App\Entity\Atelierenligne;
use App\Repository\AtelierenligneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontAtelierController extends AbstractController
{
    #[Route('/front/atelier', name: 'app_front_atelier', methods: ['GET'])]
    public function index(AtelierenligneRepository $atelierenligneRepository): Response
    {
        // Récupérer la date actuelle et ignorer l'heure
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        // Utiliser QueryBuilder pour filtrer et trier les ateliers
        $ateliers = $atelierenligneRepository->createQueryBuilder('a')
            ->where('a.datecours > :today')
            ->setParameter('today', $today)
            ->orderBy('a.datecours', 'ASC') // Tri par date croissante (du plus proche au plus éloigné)
            ->getQuery()
            ->getResult();

        return $this->render('frontOffice/HomePage/front_atelier/frontatelier.html.twig', [
            'atelierenlignes' => $ateliers,
        ]);
    }

}
