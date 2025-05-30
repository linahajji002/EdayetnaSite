<?php

namespace App\Controller;

use App\Entity\Atelierenligne;
use App\Entity\User;
use App\Form\AtelierenligneType;
use App\Repository\AtelierenligneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/atelierenligne')]
final class AtelierenligneController extends AbstractController
{
    #[Route('/admin', name: 'app_atelierenligneadmin', methods: ['GET'])]
    public function indexadmin(Request $request, PaginatorInterface $paginator, AtelierenligneRepository $atelierenligneRepository): Response
    {
        // Récupérer tous les ateliers triés par date du cours (ordre croissant)
        $atelierenlignesQuery = $atelierenligneRepository->createQueryBuilder('a')
            ->orderBy('a.datecours', 'DESC')
            ->getQuery();
        
        // Récupérer tous les ateliers sans pagination pour les statistiques
        $atelierenlignes = $atelierenligneRepository->findBy([], ['datecours' => 'ASC']);

        // Calculer les statistiques sur tous les ateliers
        $stats = [];

        foreach ($atelierenlignes as $atelier) {
            $stats[] = [
                'titre' => $atelier->getTitre(), // Titre de l'atelier
                'inscriptions' => count($atelier->getInscription()), // Nombre d'inscrits
            ];
        }

    
        // Trier les statistiques par nombre d'inscriptions (du plus grand au plus petit)
        usort($stats, function ($a, $b) {
            return $b['inscriptions'] <=> $a['inscriptions']; // Tri décroissant
        });

        // Extraire les labels et data triés
        $labels = array_column($stats, 'titre');
        $data = array_column($stats, 'inscriptions');
        
        // Paginer les ateliers pour l'affichage
        $pagination = $paginator->paginate(
            $atelierenlignesQuery,
            $request->query->getInt('page', 1), // Page actuelle
            5 // Nombre d'éléments par page
        );

        $totalItems = $pagination->getTotalItemCount();
        $pageSize = 5;
        $pageCount = ceil($totalItems / $pageSize);

        // Get previous and next page numbers, ensuring they are within valid bounds
        $currentPage = $pagination->getCurrentPageNumber();
        $previousPage = $currentPage > 1 ? $currentPage - 1 : 1;
        $nextPage = $currentPage < $pageCount ? $currentPage + 1 : $pageCount;

        // Render the template with pagination and page links
        return $this->render('admin/atelier/atelieradmin.html.twig', [
            'atelierenlignes' => $pagination,
            'previousPage' => $previousPage,
            'nextPage' => $nextPage,
            'pageCount' => $pageCount,
            'labels' => $labels, // Titres des ateliers triés
            'data' => $data,     // Nombre d'inscrits triés
        ]);
    }

    #[Route('', name: 'app_atelierenligne', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator, AtelierenligneRepository $atelierenligneRepository,Security $security)
    {
        $user = $security->getUser(); // Récupérer l'utilisateur connecté

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos ateliers.');
        }

        $id_user = $user->getId(); // Récupérer l'ID de l'utilisateur connecté

        $atelierenlignesQuery = $atelierenligneRepository->createQueryBuilder('a')
        ->where('a.id_user = :id_user')
        ->setParameter('id_user', $id_user)
        ->orderBy('a.datecours', 'ASC')
        ->getQuery();

    // Paginate the results
    $pagination = $paginator->paginate(
        $atelierenlignesQuery,
        $request->query->getInt('page', 1),
        5
    );
    

    $totalItems = $pagination->getTotalItemCount();
    $pageSize = 5;
    $pageCount = ceil($totalItems / $pageSize);

    // Get previous and next page numbers, ensuring they are within valid bounds
    $currentPage = $pagination->getCurrentPageNumber();
    $previousPage = $currentPage > 1 ? $currentPage - 1 : 1;
    $nextPage = $currentPage < $pageCount ? $currentPage + 1 : $pageCount;

    // Render the template with pagination and page links
    return $this->render('admin/atelier/atelier.html.twig', [
        'atelierenlignes' => $pagination,
        'previousPage' => $previousPage,
        'nextPage' => $nextPage,
        'pageCount' => $pageCount,
    ]);
    }

    #[Route('/new', name: 'app_atelierenligne_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour créer un atelier.');
        }

        $atelierenligne = new Atelierenligne();
        $atelierenligne->setIdUser($user);

        $form = $this->createForm(AtelierenligneType::class, $atelierenligne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($atelierenligne);
            $entityManager->flush();

            return $this->redirectToRoute('app_atelierenligne', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/atelier/new.html.twig', [
            'atelierenligne' => $atelierenligne,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_atelierenligne_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Atelierenligne $atelierenligne, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AtelierenligneType::class, $atelierenligne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_atelierenligne', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/atelier/edit.html.twig', [
            'atelierenligne' => $atelierenligne,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_atelierenligne_deleteadmin', methods: ['POST'])]
    public function deleteadmin(Request $request, Atelierenligne $atelierenligne, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('deleteadmin' . $atelierenligne->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($atelierenligne);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_atelierenligneadmin', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_atelierenligne_delete', methods: ['POST'])]
    public function delete(Request $request, Atelierenligne $atelierenligne, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $atelierenligne->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($atelierenligne);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_atelierenligne', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/search', name: 'recherche_ateliers', methods: ['GET'])]
    public function search(Request $request, AtelierenligneRepository $atelierenligneRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return new JsonResponse([]);
        }

        $atelierenlignes = $atelierenligneRepository->searchByTerm($query);

        $results = [];

        foreach ($atelierenlignes as $atelierenligne) {
            $results[] = [
                'id' => $atelierenligne->getId(),
                'titre' => $atelierenligne->getTitre(),
            ];
        }

        return new JsonResponse($results);
    }
}



