<?php 
// front visiteur
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MateriauxRepository;

class FrontUser extends AbstractController
{
    #[Route('/front', name: 'front')]
    public function index(MateriauxRepository $materiauxRepository): Response
    {
        $materiauxes = $materiauxRepository->findAll();

        // Vérifier si les données sont bien récupérées
        if (empty($materiauxes)) {
            dump("Aucun matériau trouvé !"); // Affiche un message dans la barre de debug de Symfony
        }
        return $this->render('FrontOffice\HomePage\home.html.twig', [
            'materiauxes' => $materiauxes, 
        ]);
    }

   
}
