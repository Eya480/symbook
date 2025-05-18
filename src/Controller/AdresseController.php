<?php

namespace App\Controller;

use App\Entity\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdresseController extends AbstractController
{
    #[IsGranted("ROLE_USER")]
    #[Route('/ajouter-adresse', name: 'app_ajouter_adresse', methods: ['POST'])]
    public function ajouterAdresse(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Données invalides'
            ], 400);
        }

        $adresse = new Adresse();
        $adresse->setNom($data['nom']);
        $adresse->setRue($data['rue']);
        $adresse->setCodePostal($data['codePostal']);
        $adresse->setVille($data['ville']);
        $adresse->setPays($data['pays']);
        $adresse->setUtilisateur($this->getUser());

        try {
            $em->persist($adresse);
            $em->flush();

            return $this->json([
                'success' => true,
                'id' => $adresse->getId(),
                'html' => $this->renderView('commande/_adresse_option.html.twig', [
                    'adresse' => $adresse,
                    'loop' => ['index' => 0]
                ])
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ], 500);
        }
    }
}
