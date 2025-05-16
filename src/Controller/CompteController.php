<?php

// src/Controller/CompteController.php
namespace App\Controller;

use App\Entity\Commande;
use App\Enum\EStatutCom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted("ROLE_USER")]
class CompteController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/mon-compte', name: 'app_compte')]
    public function index(): Response
    {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        return $this->render('compte/index.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/mon-compte/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(): Response
    {
        $user = $this->getUser();
        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(
            ['utilisateur' => $user],
            ['dateCommande' => 'DESC']
        );

        return $this->render('compte/mes_commandes.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/mon-compte/commande/{id}/annuler', name: 'app_annuler_commande', methods: ['POST'])]
    public function annulerCommande(Commande $commande, Request $request): Response
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $commande->setStatus(EStatutCom::CANCELLED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Commande #' . $commande->getReference() . ' annulée avec succès');
        return $this->redirectToRoute('app_mes_commandes');
    }
}
