<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\FiltrageService;
use App\Service\PdfService;


#[Route('/reclamation')]
class ReclamationController extends AbstractController
{

    private $filtrageService;
    

    public function __construct(FiltrageService $filtrageService)
    {
        $this->filtrageService = $filtrageService;
        
    }


    #[Route('/client/liste', name: 'app_reclamation_liste_client')]
    public function listeClient(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser(); // Récupération de l'utilisateur connecté
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos réclamations.');
        }

        $queryBuilder = $entityManager->getRepository(Reclamation::class)->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user);
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            4 
        );

        $reclamation = new Reclamation();
        $reclamation->setStatut('En attente');
        
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setUser($user);
            $entityManager->persist($reclamation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réclamation ajoutée avec succès.');
            
            return $this->redirectToRoute('app_reclamation_liste_client');
        }
    
        return $this->render('FrontOffice/HomePage/client/reclamation/liste.html.twig', [
            'reclamations' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/listereclamation', name: 'app_reclamation_liste_admin')]
    public function liste(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response    
    {
        $queryBuilder = $entityManager->getRepository(Reclamation::class)->createQueryBuilder('r')
            ->where('r.statut IN (:statuts)')
            ->setParameter('statuts', ['En attente', 'En cours']);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            4 
        );
        
        $totalReclamations = $entityManager->getRepository(Reclamation::class)->count([]);
        
        $statuses = ['En attente', 'En cours', 'Répondue'];
        $countsByStatus = [];
        foreach ($statuses as $status) {
            $countsByStatus[$status] = $entityManager->getRepository(Reclamation::class)->count(['statut' => $status]);
        }

        $percentages = [];
        foreach ($countsByStatus as $status => $count) {
            $percentages[$status] = $totalReclamations > 0 ? ($count / $totalReclamations) * 100 : 0;
        }

        return $this->render('admin/reclamation/liste.html.twig', [
            'pagination' => $pagination,
            'percentages' => $percentages,
        ]);
    }


#[Route('/modifier/{id}', name: 'app_reclamation_modifier')]
public function modifier(Request $request, EntityManagerInterface $entityManager, Reclamation $reclamation): Response
{
    $form = $this->createForm(ReclamationType::class, $reclamation, [
        'attr' => ['novalidate' => 'novalidate'], 
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted()) { 
        if ($form->isValid()) { 
            $entityManager->flush(); 
            $this->addFlash('success', 'Réclamation modifiée avec succès.');
            return $this->redirectToRoute('app_reclamation_consulter_client', [
                'id' => $reclamation->getId()
            ]);
        } else {
            
            $this->addFlash('error', 'Veuillez vérifier les informations saisies.'); 
        }
    }

    return $this->render('FrontOffice/HomePage/client/reclamation/modifier.html.twig', [
        'form' => $form->createView(),
        'reclamation' => $reclamation,
    ]);
}

#[Route('/supprimer/{id}', name: 'app_reclamation_supprimer', methods: ['POST'])]
public function supprimer(EntityManagerInterface $entityManager, Reclamation $reclamation): Response
{
    $entityManager->remove($reclamation);
    $entityManager->flush();

    $this->addFlash('success', 'Réclamation supprimée avec succès.');
    return $this->redirectToRoute('app_reclamation_liste_client');
}



#[Route('/admin/{id}/consulter', name: 'app_reclamation_consulter_admin', methods: ['GET'])]
public function consulterAdmin(Reclamation $reclamation, EntityManagerInterface $entityManager): Response
{
    $reponseExiste = $reclamation->getReponse() !== null;

    $reclamationDescription = $this->filtrageService->filtrerMotsInappropries($reclamation->getDescription());

    return $this->render('admin/reclamation/consulter.html.twig', [
        'reclamation' => $reclamation,
        'reclamationDescription' => $reclamationDescription,
        'reponseExiste' => $reponseExiste,
    ]);
}

#[Route('/client/{id}/consulter', name: 'app_reclamation_consulter_client', methods: ['GET'])]
public function consulterClient(Reclamation $reclamation, EntityManagerInterface $entityManager): Response
{
    $reponseExiste = $reclamation->getReponse() !== null;

    // Filtrer les mots inappropriés dans la description
    $reclamationDescription = $this->filtrageService->filtrerMotsInappropries($reclamation->getDescription());

    return $this->render('FrontOffice/HomePage/client/reclamation/consulter.html.twig', [
        'reclamation' => $reclamation,
        'reclamationDescription' => $reclamationDescription,
        'reponseExiste' => $reponseExiste,
    ]);
}




}
