<?php
namespace App\Service;

use Knp\Snappy\Pdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdfService
{
    private $snappy;
    private $parameterBag;

    public function __construct(Pdf $snappy, ParameterBagInterface $parameterBag)
    {
        $this->snappy = $snappy;
        $this->parameterBag = $parameterBag;
    }

    public function generateReclamationPdf($reclamation)
    {
        // Récupérer le chemin absolu de l'image
        $imagePath = $this->parameterBag->get('kernel.project_dir') . '/public/img/logo 2.png';
        $imageData = base64_encode(file_get_contents($imagePath));

        // Générer le HTML du PDF
        $html = $this->generateReclamationHtml($reclamation, $imageData);

        // Utiliser wkhtmltopdf pour générer le PDF
        $pdfContent = $this->snappy->getOutputFromHtml($html, [
            'enable-local-file-access' => true, // Autoriser l'accès aux fichiers locaux
            'no-stop-slow-scripts' => true,
            'javascript-delay' => 1000,
            'viewport-size' => '1280x1024',
        ]);

        return $pdfContent;
    }

    private function generateReclamationHtml($reclamation, $base64Image)
    {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Détails de la Réclamation</title>
                <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
                <style>
                    /* Définition des polices */
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #333; background-color: #f4f4f9; margin: 0; padding: 0; }
                    h1, h2 { color: #333; font-weight: bold; }
                    .header { background-color: #8B4513; color: white; padding: 15px 20px; text-align: center; border-radius: 8px; }
                    .logo { width: 100px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 30px; }
                    th, td { padding: 12px; text-align: left; border: 1px solid #ddd; border-radius: 4px; }
                    th { background-color: #f8f8f8; color: #333; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .footer { font-size: 10px; color: #777; margin-top: 30px; text-align: center; }
                    a { color: #8B4513; text-decoration: none; font-weight: bold; }
                    a:hover { text-decoration: underline; }
                    .table-container { margin: 0 auto; max-width: 800px; }
                    .content { padding: 20px; }
                    .sub-header { background-color:rgba(255, 223, 212, 0.55); padding: 10px; font-size: 16px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='data:image/png;base64,{$base64Image}' class='logo' alt='Logo'>
                        <h1>Détails de la Réclamation</h1>
                    </div>
                    <div class='content'>
                        <div class='table-container'>
                            <div class='sub-header'>
                                <h2>Informations de la Réclamation</h2>
                            </div>
                            <table class='table'>
                                <tr><th>ID :</th><td>{$reclamation->getId()}</td></tr>
                                <tr><th>Titre :</th><td>{$reclamation->getTitre()}</td></tr>
                                <tr><th>Description :</th><td>{$reclamation->getDescription()}</td></tr>
                                <tr><th>Statut :</th><td>{$reclamation->getStatut()}</td></tr>
                                <tr><th>Date :</th><td>{$reclamation->getDateReclamation()->format('Y-m-d')}</td></tr>
                                <tr><th>Réponse :</th><td>{$reclamation->getReponse()->getDescription()}</td></tr>
                                <tr><th>Date de la Réponse :</th><td>{$reclamation->getReponse()->getDateReponse()->format('Y-m-d')}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>Généré le : " . (new \DateTime())->format('Y-m-d H:i:s') . "</p>
                        <p><a href='http://127.0.0.1:8000/reclamation/client/liste' target='_blank'>Cliquez ici pour voir toutes vos réclamations</a></p>
                    </div>
                </div>
            </body>
        </html>
        ";
    }
}
