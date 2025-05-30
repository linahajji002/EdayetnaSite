<?php

namespace App\Controller;

use App\Entity\Inscriptionatelier;
use App\Entity\Atelierenligne;
use App\Entity\User;
use App\Repository\InscriptionatelierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;

class InscriptionAtelierController extends AbstractController
{
    #[Route('/atelier/inscription/{id}', name: 'app_inscription_atelier')]
    public function inscrire(
        int $id,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        MailerInterface $mailer,
        Security $security
    ): Response {
        // Récupérer l'utilisateur connecté
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos ateliers.');
        }

        $userId = $user->getId();

        $atelier = $entityManager->getRepository(Atelierenligne::class)->find($id);
        if (!$atelier) {
            throw $this->createNotFoundException('Atelier non trouvé.');
        }

        // Vérifier si l'utilisateur est déjà inscrit
        $existingInscription = $entityManager->getRepository(Inscriptionatelier::class)->findOneBy([
            'id_user' => $user,
            'atelier' => $atelier
        ]);

        if ($existingInscription) {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet atelier.');
            return $this->redirectToRoute('app_inscriptions');
        }

        $inscription = new Inscriptionatelier();
        $inscription->setIdUser($user);
        $inscription->setAtelier($atelier);
        $inscription->setDateinscri(new \DateTime());
        $inscription->setStatut('À venir');

        $entityManager->persist($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription réussie à l\'atelier : ' . $atelier->getTitre());

        $email = (new Email())
            ->from('edayetna2025@gmail.com')
            ->to($user->getEmail())
            ->subject('Confirmation d\'inscription à l\'atelier')
            ->html(
                '<p>Bonjour ' . $user->getNom() . ',</p>
                <p>Merci de vous être inscrit à l\'atelier : <strong>' . $atelier->getTitre() . '</strong>.</p>
                <p>La session aura lieu le <strong>' . $atelier->getDatecours()->format("d/m/Y") . '</strong> à <strong>' . $atelier->getDatecours()->format("H:i") . '</strong>.</p>
                <p>Nous avons hâte de vous y voir !</p>
                <p>Cordialement,<br> L\'équipe YEDAYETNA</p>'
            );

        $mailer->send($email);

        // Récupérer les inscriptions de l'utilisateur connecté
        $inscriptionsQuery = $entityManager->getRepository(Inscriptionatelier::class)->createQueryBuilder('i')
            ->leftJoin('i.atelier', 'a')
            ->where('i.id_user = :id_user')
            ->setParameter('id_user', $userId)
            ->orderBy('a.datecours', 'DESC')
            ->getQuery();

        $inscriptions = $inscriptionsQuery->getResult();

        return $this->render('frontOffice/HomePage/front_atelier/inscription_atelier/inscriptionatelier.html.twig', [
            'inscriptions' => $inscriptions,
            'flash_message' => 'Vous êtes inscrit à un nouvel atelier!'
        ]);
    }

    #[Route('/inscriptionclient', name: 'app_inscriptions', methods: ['GET'])]
    public function showinscri(
        InscriptionatelierRepository $inscriptionsatelierRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $user = $security->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos ateliers.');
        }

        $userId = $user->getId();

        // Récupérer les inscriptions triées par date de cours décroissante
        $inscriptionsQuery = $inscriptionsatelierRepository->createQueryBuilder('i')
            ->leftJoin('i.atelier', 'a')
            ->where('i.id_user = :id_user')
            ->setParameter('id_user', $userId)
            ->orderBy('a.datecours', 'DESC')
            ->getQuery();

        $inscriptions = $inscriptionsQuery->getResult();

        $today = new DateTime();

        foreach ($inscriptions as $inscription) {
            $atelier = $inscription->getAtelier();
            $dateCours = $atelier->getDateCours();

            if ($dateCours === null) {
                $inscription->setStatut('Date non définie');
            } elseif ($dateCours instanceof DateTime) {
                $dateAtelier = $dateCours;
            } else {
                $dateAtelier = new DateTime($dateCours);
            }

            // Mise à jour du statut selon la date
            if ($dateAtelier < $today) {
                $inscription->setStatut('Terminé');
            } elseif ($dateAtelier > $today) {
                $inscription->setStatut('À venir');
            } else {
                $inscription->setStatut('En cours aujourd\'hui');
            }

            $entityManager->persist($inscription);
        }

        $entityManager->flush();

        return $this->render('frontOffice/HomePage/front_atelier/inscription_atelier/inscriptionatelier.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/atelier/annulation/{id}', name: 'app_annulation', methods: ['POST'])]
    public function delete(
        Request $request,
        Inscriptionatelier $inscriptionatelier,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $inscriptionatelier->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($inscriptionatelier);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_inscriptions', [], Response::HTTP_SEE_OTHER);
    }
}
