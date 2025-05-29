<?php
namespace App\Controller;


use App\Entity\Reclamation;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FiltrageService;

class PdfController extends AbstractController
{
    #[Route('/generate-pdf/{id}', name: 'generate_pdf')]
    
    public function generatePdf(int $id, PdfService $pdfService, EntityManagerInterface $entityManager, FiltrageService $filtrageService): Response
    {
        $reclamation = $entityManager->getRepository(Reclamation::class)->find($id);

        if (!$reclamation) {
            throw $this->createNotFoundException("Réclamation non trouvée.");
        }
        $reclamationDescription = $filtrageService->filtrerMotsInappropries($reclamation->getDescription());
        
        // Mettre à jour la description de la réclamation
        $reclamation->setDescription($reclamationDescription);

        // Générer le PDF via le service
        $pdfContent = $pdfService->generateReclamationPdf($reclamation);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reclamation.pdf"',
        ]);
    }
    
}