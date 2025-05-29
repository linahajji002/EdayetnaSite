<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Lignedecommande;
use App\Entity\Produit;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\LignedecommandeRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Knp\Snappy\Pdf;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Dompdf\Options;  // ✅ Make sure this line is present!

#[Route('/commande')]
final class CommandeController extends AbstractController{
    #[Route('/backcommande', name: 'app_commande_index', methods: ['GET'])]
    public function index(Request $request, CommandeRepository $commandeRepository): Response
    {
        $searchTerm = $request->query->get('search');

        if ($searchTerm) {
            $commandes = $commandeRepository->createQueryBuilder('c')
                ->where('c.statut LIKE :search OR c.adresse_livraison LIKE :search OR c.paiement LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();

            return $this->json([
                'commandes' => array_map(fn($commande) => [
                    'id' => $commande->getId(),
                    'dateCommande' => $commande->getDateCommande()->format('Y-m-d'),
                    'montantTotal' => $commande->getMontantTotal(),
                    'statut' => $commande->getStatut(),
                    'adresseLivraison' => $commande->getAdresseLivraison(),
                    'paiement' => $commande->getPaiement(),
                ], $commandes),
            ]);
        }

        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }

    #[Route('/newcommande', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commande);
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/commandedelet/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/panier/ajouter/{id}', name: 'ajouter_panier')]
    public function ajouterAuPanier(Produit $produit, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        // ✅ 1. Check if the user already has an active order
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'id_user' => $user,
            'statut' => 'En attente' // We only take active carts
        ]);

        if (!$commande) {
            // ✅ 2. Create a new cart (commande)
            $commande = new Commande();
            $commande->setIdUser($user);
            $commande->setStatut('En attente');
            $commande->setDateCommande(new \DateTime());
            $commande->setMontantTotal(0); // Placeholder, will be calculated later
            $commande->setAdresseLivraison('A définir'); // Placeholder for address
            $commande->setPaiement('Non défini'); // Placeholder for payment
            $entityManager->persist($commande);
        }

        // ✅ 3. Check if the product is already in the cart
        $ligne = $entityManager->getRepository(Lignedecommande::class)->findOneBy([
            'id_commande' => $commande,
            'id_produit' => $produit // ✅ We added this field in `Lignedecommande`
        ]);

        if ($ligne) {
            // ✅ 4. If product exists, increase quantity
            $ligne->setQuantite($ligne->getQuantite() + 1);
        } else {
            // ✅ 5. If product does not exist, create a new line
            $ligne = new Lignedecommande();
            $ligne->setIdCommande($commande);
            $ligne->setIdProduit($produit); // ✅ Corrected field
            $ligne->setQuantite(1);
            $ligne->setPrixUnitaire($produit->getPrix());
            $entityManager->persist($ligne);
        }

        // ✅ 6. Save changes in the database
        $entityManager->flush();

        // ✅ 7. Redirect to view the cart
        return $this->redirectToRoute('voir_panier');
    }

    #[Route('/panier', name: 'voir_panier')]
    public function voirPanier(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        // ✅ 1. Get the user's active cart
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'id_user' => $user,
            'statut' => 'En attente' // We only take active carts
        ]);

        // ✅ 2. If no cart exists, redirect to products
        if (!$commande) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_produit_index');
        }

        // ✅ 3. Get all products inside the cart (LigneDeCommande)
        $lignes = $entityManager->getRepository(Lignedecommande::class)->findBy([
            'id_commande' => $commande
        ]);

        // ✅ 4. Render the cart page
        return $this->render('panier/index.html.twig', [
            'lignes' => $lignes,
            'commande' => $commande
        ]);
    }
    #[Route('/panier/supprimer/{id}', name: 'supprimer_du_panier')]
    public function supprimerDuPanier(Lignedecommande $ligne, EntityManagerInterface $entityManager): Response
    {
        // ✅ 1. Remove the product from cart
        $entityManager->remove($ligne);
        $entityManager->flush();

        // ✅ 2. Redirect back to the cart
        return $this->redirectToRoute('voir_panier');
    }



//    #[Route('/commande/valider', name: 'valider_commande', methods: ['POST'])]
//    public function validerCommande(Request $request, EntityManagerInterface $entityManager, Security $security, MailerInterface $mailer, FlashyNotifier $flashy,PaymentService $paymentService): Response
//    {
//        $user = $security->getUser();
//
//        // ✅ Find the user's pending order
//        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
//            'id_user' => $user,
//            'statut' => 'En attente'
//        ]);
//
//        // ✅ Handle case where no order exists
//        if (!$commande) {
//            $this->addFlash('warning', 'Aucune commande en attente.');
//            return $this->redirectToRoute('app_produit_index');
//        }
//
//        // ✅ Fetch form input safely
//        $adresse = $request->request->get('adresse');
//        $paiement = $request->request->get('paiement');
//
//        if (!$adresse || !$paiement) {
//            $this->addFlash('danger', 'Veuillez remplir tous les champs.');
//            return $this->redirectToRoute('voir_panier');
//        }
//
//        // ✅ Fetch LigneDeCommande manually
//        $ligneCommandes = $entityManager->getRepository(Lignedecommande::class)->findBy([
//            'id_commande' => $commande
//        ]);
//
//        if (!$ligneCommandes) {
//            $this->addFlash('danger', 'Aucune ligne de commande trouvée.');
//            return $this->redirectToRoute('voir_panier');
//        }
//
//        // ✅ Check stock before processing
//        foreach ($ligneCommandes as $ligne) {
//            $produit = $ligne->getIdProduit();
//            $quantiteDemandee = $ligne->getQuantite();
//
//            if ($produit->getStock() < $quantiteDemandee) {
//                $this->addFlash('danger', "Stock insuffisant pour le produit '{$produit->getNomProduit()}'. Disponible: {$produit->getStock()}, demandé: {$quantiteDemandee}.");
//                return $this->redirectToRoute('voir_panier'); // ❌ STOP validation here if any product is out of stock
//            }
//        }
//
//        // ✅ Stock is sufficient, proceed with order
//        $totalAmount = 0;
//        foreach ($ligneCommandes as $ligne) {
//            $produit = $ligne->getIdProduit();
//            $quantiteDemandee = $ligne->getQuantite();
//            $prixUnitaire = $ligne->getPrixUnitaire();
//
//            // ✅ Deduct stock
//            $produit->setStock($produit->getStock() - $quantiteDemandee);
//            $totalAmount += $quantiteDemandee * $prixUnitaire;
//        }
//
//        // ✅ Update the order
//        $commande->setAdresseLivraison($adresse);
//        $commande->setPaiement($paiement);
//        $commande->setStatut('Confirmé');
//        $commande->setMontantTotal($totalAmount);
//
//        // ✅ Persist changes
//        $entityManager->flush();
//
//        // ✅ Send email confirmation
//        $email = (new Email())
//            ->from('hassanjebri99@gmail.com') // Change to your email
//            ->to('hassanjebri99@gmail.com') // Send to the user
//            ->subject('Confirmation de votre commande')
//            ->html("
//            <h1>Merci pour votre commande !</h1>
//            <p>Bonjour,</p>
//            <p>Votre commande a été confirmée avec succès.</p>
//            <h3>Détails de la commande :</h3>
//            <ul>
//                <li>Date : " . $commande->getDateCommande()->format('d-m-Y') . "</li>
//                <li>Montant total : " . number_format($commande->getMontantTotal(), 2) . "€</li>
//                <li>Adresse de livraison : {$commande->getAdresseLivraison()}</li>
//                <li>Paiement : {$commande->getPaiement()}</li>
//            </ul>
//            <p>Nous vous remercions de votre confiance.</p>
//        ");
//        $products[] = [
//            'name' => $produit->getNomProduit(),
//            'amount' => (int)($prixUnitaire * 100), // Convert to cents
//            'quantity' => $quantiteDemandee,
//        ];
//
//        $mailer->send($email);
//        $stripeSessionUrl = $paymentService->createCheckoutSession($products, $commande->getId());
//
//        if (!$stripeSessionUrl) {
//            $this->addFlash('danger', 'Erreur lors de la création de la session de paiement.');
//            return $this->redirectToRoute('voir_panier');
//        }
//
//        return $this->redirect($stripeSessionUrl);
//        // ✅ Flashy Notification for Success
//        $flashy->success('Votre commande a été validée avec succès !', 'confirmation_commande');
//
//        // ✅ Redirect to confirmation page
//     //  return $this->redirectToRoute('confirmation_commande11', ['commandeId' => $commande->getId()]);
//    }

    #[Route('/commande/valider', name: 'valider_commande', methods: ['POST'])]
    public function validerCommande(Request $request, EntityManagerInterface $entityManager, Security $security, MailerInterface $mailer, FlashyNotifier $flashy, PaymentService $paymentService, SessionInterface $session): Response
    {
        $user = $security->getUser();

        // ✅ Find the user's pending order
        $commande = $entityManager->getRepository(Commande::class)->findOneBy([
            'id_user' => $user,
            'statut' => 'En attente'
        ]);

        if (!$commande) {
            $this->addFlash('warning', 'Aucune commande en attente.');
            return $this->redirectToRoute('app_produit_index');
        }

        // ✅ Fetch form input safely
        $adresse = $request->request->get('adresse');
        $paiement = $request->request->get('paiement');

        if (!$adresse || !$paiement) {
            $this->addFlash('danger', 'Veuillez remplir tous les champs.');
            return $this->redirectToRoute('voir_panier');
        }

        // ✅ Fetch LigneDeCommande manually
        $ligneCommandes = $entityManager->getRepository(Lignedecommande::class)->findBy([
            'id_commande' => $commande
        ]);

        if (!$ligneCommandes) {
            $this->addFlash('danger', 'Aucune ligne de commande trouvée.');
            return $this->redirectToRoute('voir_panier');
        }

        // ✅ Validate stock and calculate total amount
        $totalAmount = 0;
        foreach ($ligneCommandes as $ligne) {
            $produit = $ligne->getIdProduit();
            $quantiteDemandee = $ligne->getQuantite();
            $prixUnitaire = $ligne->getPrixUnitaire();

            if ($produit->getStock() < $quantiteDemandee) {
                $this->addFlash('danger', "Stock insuffisant pour le produit '{$produit->getNomProduit()}'. Disponible: {$produit->getStock()}, demandé: {$quantiteDemandee}.");
                return $this->redirectToRoute('voir_panier');
            }

            // ✅ Deduct stock
            $produit->setStock($produit->getStock() - $quantiteDemandee);
            $totalAmount += $quantiteDemandee * $prixUnitaire;
        }

        // ✅ Update the order
        $commande->setAdresseLivraison($adresse);
        $commande->setPaiement($paiement);
        $commande->setStatut('Confirmé');
        $commande->setMontantTotal($totalAmount);

        $entityManager->flush();

        // ✅ Send email confirmation
        $email = (new Email())
            ->from('edayetna2025@gmail.com')
            ->to('letaief.hazem4030@gmail.com') // Send to user's email
            ->subject('Confirmation de votre commande')
            ->html("
            <h1>Merci pour votre commande !</h1>
            <p>Bonjour </p>
            <p>Votre commande <strong>#{$commande->getId()}</strong> a été confirmée avec succès.</p>
            <p>Montant total: <strong>{$totalAmount}€</strong></p>
            <p>Adresse de livraison: <strong>{$adresse}</strong></p>
            <p>Merci pour votre confiance !</p>
        ");

        $mailer->send($email);

        // ✅ Prepare Stripe payment session
        $products = [];
        foreach ($ligneCommandes as $ligne) {
            $products[] = [
                'name' => $ligne->getIdProduit()->getNomProduit(),
                'amount' => (int)($ligne->getPrixUnitaire() * 100),
                'quantity' => $ligne->getQuantite(),
            ];
        }

        $stripeSessionUrl = $paymentService->createCheckoutSession($products, $commande->getId());

        if (!$stripeSessionUrl) {
            $this->addFlash('danger', 'Erreur lors de la création de la session de paiement.');
            return $this->redirectToRoute('voir_panier');
        }

        // ✅ Store Stripe session URL in the Symfony session
        $session->set('stripe_session_url', $stripeSessionUrl);
        $session->set('commande_id', $commande->getId());

        // ✅ Redirect to confirmation page
        return $this->redirectToRoute('confirmation_commande11');
    }






    #[Route('/commande/confirmation/ok', name: 'confirmation_commande', methods: ['GET'])]
    public function confirmation(): Response
    {
        return $this->render('commande/confirmation.html.twig');
    }
    #[Route('/okkk', name: 'confirmation_commande11')]
    public function confirmation111(SessionInterface $session): Response
    {
        // ✅ Retrieve Stripe session URL from the session
        $stripeSessionUrl = $session->get('stripe_session_url', null);
        $commandeId = $session->get('commande_id', null);

        if (!$stripeSessionUrl || !$commandeId) {
            $this->addFlash('danger', 'Session de paiement introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        return $this->render('commande/confirmation1.html.twig', [
            'stripeSessionUrl' => $stripeSessionUrl,
            'commandeId' => $commandeId
        ]);
    }


    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/pdfcommande/{id}/pdf', name: 'app_commande_pdf', methods: ['GET'])]
    public function generatePdf(Commande $commande, Environment $twig): Response
    {
        // ✅ Generate the HTML from Twig template
        $html = $twig->render('commande/pdf_template.html.twig', [
            'commande' => $commande
        ]);

        // ✅ PDF options
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true); // ✅ Allow external images

        // ✅ Initialize Dompdf
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);

        // ✅ Set paper size (A4)
        $dompdf->setPaper('A4', 'portrait');

        // ✅ Render the HTML as PDF
        $dompdf->render();

        // ✅ Stream the generated PDF to the user
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="commande_' . $commande->getId() . '.pdf"',
            ]
        );
    }

    #[Route('/commande/stripe-payment', name: 'commande_stripe_payment', methods: ['POST'])]
    public function createStripeSession(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $commandeId = $data['commandeId'] ?? null;

        if (!$commandeId) {
            return new JsonResponse(['error' => 'Commande non trouvée.'], 400);
        }

        $commande = $entityManager->getRepository(Commande::class)->find($commandeId);

        if (!$commande) {
            return new JsonResponse(['error' => 'Commande non trouvée.'], 400);
        }

        Stripe::setApiKey("sk_test_51QCAHpBDW3LkkcbKFv9eNqLWddEC9JLkjAoZkB6VC6e52cHjojXS5vlEoWlZQWCeeAaN67bpZllUrR9RWehULuup00lCYHx3bX");

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Commande #' . $commande->getId()],
                    'unit_amount' => $commande->getMontantTotal() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'http://127.0.0.1:8000/commande/success',
            'cancel_url' => 'http://127.0.0.1:8000/commande/cancel',
        ]);

        return new JsonResponse(['sessionId' => $session->id]);
    }
    #[Route('/stats', name: 'commande_stats', methods: ['GET'])]
    public function stats(CommandeRepository $commandeRepository, LignedecommandeRepository $ligneCommandeRepository): Response
    {
        $totalOrders = $commandeRepository->count([]);
        $totalRevenue = $commandeRepository->createQueryBuilder('c')
            ->select('SUM(c.montant_total)')
            ->getQuery()
            ->getSingleScalarResult();

        // Fetch top 5 best-selling products
        $topProducts = $ligneCommandeRepository->createQueryBuilder('lc')
            ->select('p.nom_produit AS name, SUM(lc.quantite) AS total_sold')
            ->join('lc.id_produit', 'p')
            ->groupBy('p.id')
            ->orderBy('total_sold', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Fetch order status distribution
        $statusCounts = $commandeRepository->createQueryBuilder('c')
            ->select('c.statut AS status, COUNT(c.id) AS count')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();

        return $this->render('commande/stat.html.twig', [
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'topProducts' => json_encode($topProducts),
            'statusCounts' => json_encode($statusCounts),
        ]);
    }
}
