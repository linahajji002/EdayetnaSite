<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Lignedecommande;
use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twilio\Rest\Client;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route(name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findAll();

        // Debug: Check if products have a promotion
        foreach ($produits as $produit) {
            dump($produit->getIdPromotion()); // This should not be null for promoted products
        }

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/backproduit', name: 'app_produit_indexback', methods: ['GET'])]
    public function indexback(Request $request, ProduitRepository $produitRepository): Response
    {
        $searchTerm = $request->query->get('search');

        // If a search term is provided, filter the results
        if ($searchTerm) {
            $produits = $produitRepository->createQueryBuilder('p')
                ->where('p.nom_produit LIKE :search OR p.categorie LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();

            return $this->json([
                'produits' => array_map(fn($produit) => [
                    'id' => $produit->getId(),
                    'nomProduit' => $produit->getNomProduit(),
                    'categorie' => $produit->getCategorie(),
                    'prix' => $produit->getPrix(),
                    'stock' => $produit->getStock(),
                    'statut' => $produit->getStatut(),
                ], $produits),
            ]);
        }

        return $this->render('produit/indexbackproduit.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }


    private function sendTwilioMessage(string $message): void
    {
        $twilioAccountSid = $this->getParameter('twilio_account_sid');
        $twilioAuthToken = $this->getParameter('twilio_auth_token');
        $twilioPhoneNumber = $this->getParameter('twilio_phone_number');

        $twilioClient = new Client($twilioAccountSid, $twilioAuthToken);

        $twilioClient->messages->create(
            '+21655111272', // Replace with the recipient's phone number
            [
                'from' => $twilioPhoneNumber,
                'body' => $message, // Accepts a string message now
            ]
        );
    }




    #[Route('/newproduit', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'utilisateur connecté au produit
            $produit->setIdUser($this->getUser());
    
            // Gestion de l'image
            $file = $form['image']->getData();
            if ($file) {
                $fileName = uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('image_directory'),
                    $fileName
                );
                $produit->setImage($fileName);
            }
    
            // Sauvegarde en base de données
            $entityManager->persist($produit);
            $entityManager->flush();
    
            // Envoi de SMS
            $message = "📢 Nouveau produit ajouté: \n" .
                "📦 Nom: " . $produit->getNomProduit() . "\n" .
                "🏷 Catégorie: " . $produit->getCategorie() . "\n" .
                "💰 Prix: " . $produit->getPrix() . " TND\n" .
                "📦 Stock: " . $produit->getStock() . "\n" .
                "🔗 Voir le produit: https://yourwebsite.com/produit/" . $produit->getId();
           
                
    
            try {
                $this->sendTwilioMessage($message);
                $this->addFlash('success', 'Produit ajouté et SMS envoyé avec succès!');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Produit ajouté mais échec d\'envoi du SMS: ' . $e->getMessage());
            }
    
        }
    
        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }
    






    #[Route('/produit/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {


        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }
    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the newly-uploaded file (if any)
            $file = $form['image']->getData();

            // If a new file was uploaded
            if ($file) {
                $fileName = uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('image_directory'),
                    $fileName
                );

                // Update the entity with the new filename
                $produit->setImage($fileName);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }


    #[Route('/produitdelete/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/sort/trie', name: 'app_produit_trier', methods: ['GET'])]
    public function trier(Request $request, ProduitRepository $produitRepository): Response
    {
        $sortField = $request->query->get('sort', 'id'); // Default sorting by ID
        $sortOrder = $request->query->get('order', 'asc'); // Default order is ascending

        // ✅ Allow sorting only by 'prix' or 'stock'
        if (!in_array($sortField, ['prix', 'stock'])) {
            $sortField = 'id'; // Fallback to default
        }

        // ✅ Fetch sorted products
        $produits = $produitRepository->createQueryBuilder('p')
            ->orderBy('p.' . $sortField, $sortOrder)
            ->getQuery()
            ->getResult();

        return $this->render('produit/trier_produits.html.twig', [
            'produits' => $produits,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
        ]);
    }
    #[Route('/stats/stat', name: 'produit_stats', methods: ['GET'])]
    public function stats(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findAll();
        $promotedProducts = $produitRepository->findTopPromotedProducts(2); // Fetch only top 2

        // Count promoted and non-promoted products
        $totalProducts = count($produits);
        $promotedCount = count(array_filter($produits, fn($p) => $p->getIdPromotion() !== null));
        $nonPromotedCount = $totalProducts - $promotedCount;

        // Prepare data for the chart
        $chartLabels = ["Produits sans Promotion"];
        $chartData = [$nonPromotedCount];
        $chartColors = ["#36A2EB"];

        foreach ($promotedProducts as $produit) {
            $label = $produit->getNomProduit() . " (" . $produit->getIdPromotion()->getPrixNouv() . " TND)";
            $chartLabels[] = $label;
            $chartData[] = 1; // Each promoted product gets its own count
            $chartColors[] = "#FF6384"; // Red for promoted products
        }

        return $this->render('produit/stat.html.twig', [
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            'chartColors' => json_encode($chartColors),
        ]);
    }
    #[Route('/stats/stat2', name: 'produit_stats2', methods: ['GET'])]
    public function stats2(ProduitRepository $produitRepository): Response
    {
        $totalProducts = $produitRepository->count([]);
        $promotedProductsCount = $produitRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.id_promotion IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $nonPromotedProductsCount = $totalProducts - $promotedProductsCount;

        $averagePrice = $produitRepository->createQueryBuilder('p')
            ->select('AVG(p.prix)')
            ->getQuery()
            ->getSingleScalarResult();

        $mostExpensiveProduct = $produitRepository->createQueryBuilder('p')
            ->select('p.nom_produit, p.prix')
            ->orderBy('p.prix', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $leastExpensiveProduct = $produitRepository->createQueryBuilder('p')
            ->select('p.nom_produit, p.prix')
            ->orderBy('p.prix', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Fetch category-wise product distribution
        $categoryDistribution = $produitRepository->createQueryBuilder('p')
            ->select('p.categorie AS category, COUNT(p.id) AS count')
            ->groupBy('p.categorie')
            ->getQuery()
            ->getResult();

        return $this->render('produit/stat2.html.twig', [
            'totalProducts' => $totalProducts,
            'promotedProductsCount' => $promotedProductsCount,
            'nonPromotedProductsCount' => $nonPromotedProductsCount,
            'averagePrice' => $averagePrice,
            'mostExpensiveProduct' => $mostExpensiveProduct,
            'leastExpensiveProduct' => $leastExpensiveProduct,
            'categoryDistribution' => json_encode($categoryDistribution),
        ]);
    }


    
    #[Route('/backproduitartisan', name: 'app_produitartisan_indexback', methods: ['GET'])]
    public function indexbackartisan(Request $request, ProduitRepository $produitRepository): Response
    {
        $searchTerm = $request->query->get('search');

        // If a search term is provided, filter the results
        if ($searchTerm) {
            $produits = $produitRepository->createQueryBuilder('p')
                ->where('p.nom_produit LIKE :search OR p.categorie LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();

            return $this->json([
                'produits' => array_map(fn($produit) => [
                    'id' => $produit->getId(),
                    'nomProduit' => $produit->getNomProduit(),
                    'categorie' => $produit->getCategorie(),
                    'prix' => $produit->getPrix(),
                    'stock' => $produit->getStock(),
                    'statut' => $produit->getStatut(),
                ], $produits),
            ]);
        }

        return $this->render('artisan/produitartisan.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

}
