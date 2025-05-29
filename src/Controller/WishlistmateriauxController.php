<?php

namespace App\Controller;

use App\Entity\Wishlistmateriaux;
use App\Repository\MateriauxRepository;
use App\Repository\WishlistmateriauxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;



final class WishlistmateriauxController extends AbstractController
{
    #[Route('/wishlistmateriaux', name: 'app_wishlistmateriaux')]
    public function wishlist(WishlistmateriauxRepository $wishlistRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Rediriger vers la connexion si l'utilisateur n'est pas connecté
        }

        // Récupérer la wishlist de l'utilisateur connecté
        $wishlist = $wishlistRepository->findOneBy(['user' => $user]);

        return $this->render('FrontOffice/HomePage/wishlistmateriaux/index.html.twig', [
            'wishlist' => $wishlist,
        ]);
    }



    #[Route('/wishlist/add/{id}', name: 'wishlist_add')]
    public function addToWishlist(int $id, MateriauxRepository $materiauxRepository, EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Rediriger vers la connexion si l'utilisateur n'est pas connecté
        }

        // Récupérer le matériel
        $materiel = $materiauxRepository->find($id);

        if (!$materiel) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Vérifier si l'utilisateur a déjà une wishlist
        $wishlist = $em->getRepository(Wishlistmateriaux::class)->findOneBy(['user' => $user]);

        if (!$wishlist) {
            $wishlist = new Wishlistmateriaux();
            $wishlist->setUser($user);
            $wishlist->setDateAjout(new \DateTime());
            $em->persist($wishlist);
            $em->flush();
        }

        // Vérifier si le produit est déjà dans la wishlist
        if (!$wishlist->getIdMateriel()->contains($materiel)) {
            $wishlist->addIdMateriel($materiel);
            $em->persist($wishlist);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté à votre wishlist.');
        } else {
            $this->addFlash('info', 'Ce produit est déjà dans votre wishlist.');
        }

        return $this->redirectToRoute('app_wishlistmateriaux');
    }

    
    #[Route("/wishlist/remove/{id}", name:"wishlist_remove")]
    public function removeFromWishlist(int $id, EntityManagerInterface $entityManager, WishlistmateriauxRepository $wishlistRepository, MateriauxRepository $materiauxRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app_login'); // Rediriger vers la connexion si l'utilisateur n'est pas connecté
        }
    
        // Récupérer le matériel
        $materiel = $materiauxRepository->find($id);
    
        if (!$materiel) {
            throw $this->createNotFoundException('Matériel non trouvé.');
        }
    
        // Récupérer la wishlist de l'utilisateur
        $wishlist = $wishlistRepository->findOneBy(['user' => $user]);
    
        if (!$wishlist) {
            throw $this->createNotFoundException('Wishlist introuvable.');
        }
    
        // Vérifier si le matériel est bien dans la wishlist
        if ($wishlist->getIdMateriel()->contains($materiel)) {
            $wishlist->removeIdMateriel($materiel);
            $entityManager->persist($wishlist);
            $entityManager->flush();
    
            $this->addFlash('success', 'Produit supprimé de votre wishlist.');
        } else {
            $this->addFlash('warning', 'Ce produit ne figure pas dans votre wishlist.');
        }
    
        return $this->redirectToRoute('app_wishlistmateriaux');
    }
    

}
