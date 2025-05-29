<?php

namespace App\Controller;

use App\Entity\Materiaux;
use App\Form\MateriauxType;
use App\Repository\MateriauxRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;

#[Route('/materiaux')]
final class MateriauxController extends AbstractController
{
    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos matériaux.');
        }

        return $user;
    }

    #[Route(name: 'app_materiaux_index', methods: ['GET'])]
    public function index(
        MateriauxRepository $materiauxRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $materiauxRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        $labels = [];
        $data = [];
        $lowStock = false;
        $lowStockUsers = [];

        foreach ($pagination as $materiel) {
            $labels[] = $materiel->getNomMateriel();
            $data[] = $materiel->getQuantiteStock();

            if ($materiel->getQuantiteStock() <= $materiel->getSeuilMin()) {
                $lowStock = true;

                $user = $this->getAuthenticatedUser();

                if ($user && $user->getEmail()) {
                    $lowStockUsers[$user->getEmail()][] = $materiel->getNomMateriel();
                }
            }
        }

        if ($lowStock) {
            $this->addFlash('low_stock', '⚠️ Certains matériaux ont un stock insuffisant.');
            dump('Flash ajouté : Stock insuffisant détecté');

            foreach ($lowStockUsers as $email => $materiauxNames) {
                $this->sendLowStockEmail($mailer, $email, $materiauxNames);
            }
        }

        return $this->render('admin/materiaux/index.html.twig', [
            'materiauxes' => $pagination,
            'pagination' => $pagination,
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    private function sendLowStockEmail(MailerInterface $mailer, string $recipientEmail, array $materiauxNames): void
    {
        $email = (new Email())
            ->from('ton.email@gmail.com')
            ->to('edayetna2025@gmail.com')
            ->subject('⚠️ Alerte stock : Quantité faible de matériaux')
            ->html(
                '<p>Bonjour,</p>
                 <p>Les matériaux suivants sont en rupture de stock : <strong>' . implode(', ', $materiauxNames) . '</strong>.</p>
                 <p>Merci de vérifier le stock.</p>
                 <p>Cordialement,<br> L\'équipe YEDAYETNA</p>'
            );

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            dump('❌ Erreur envoi email : ' . $e->getMessage());
        }
    }

    #[Route('/search', name: 'app_materiaux_search', methods: ['GET'])]
    public function search(Request $request, MateriauxRepository $materiauxRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return new JsonResponse([]);
        }

        $materiaux = $materiauxRepository->searchByTerm($query);

        $results = [];

        foreach ($materiaux as $materiel) {
            $results[] = [
                'id' => $materiel->getId(),
                'nomMateriel' => $materiel->getNomMateriel(),
                'quantiteStock' => $materiel->getQuantiteStock(),
                'seuilMin' => $materiel->getSeuilMin(),
                'categorie' => $materiel->getCategorie(),
                'description' => $materiel->getDescription(),
                'photo' => $materiel->getPhoto(),
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/new', name: 'app_materiaux_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $materiaux = new Materiaux();
        $form = $this->createForm(MateriauxType::class, $materiaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($materiaux);
            $entityManager->flush();

            return $this->redirectToRoute('app_materiaux_index');
        }

        return $this->render('admin/materiaux/new.html.twig', [
            'materiaux' => $materiaux,
            'form' => $form,
        ]);
    }

    #[Route('/showmateriaux/{id}', name: 'app_materiaux_show', methods: ['GET'])]
    public function show(Materiaux $materiaux): Response
    {
        return $this->render('admin/materiaux/show.html.twig', [
            'materiaux' => $materiaux,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_materiaux_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Materiaux $materiaux, EntityManagerInterface $entityManager): Response
    {
       
        $form = $this->createForm(MateriauxType::class, $materiaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_materiaux_index');
        }

        return $this->render('admin/materiaux/edit.html.twig', [
            'materiaux' => $materiaux,
            'form' => $form,
        ]);
    }

    #[Route('/materiauxdelete/{id}', name: 'app_materiaux_delete', methods: ['POST'])]
    public function delete(Request $request, Materiaux $materiaux, EntityManagerInterface $entityManager): Response
    {
        

        if ($this->isCsrfTokenValid('delete'.$materiaux->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($materiaux);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_materiaux_index');
    }
}
