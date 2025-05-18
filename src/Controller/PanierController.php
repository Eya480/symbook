<?php

namespace App\Controller;

use App\Service\PanierService;
use App\Repository\LivresRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    private $panierService;

    public function __construct(PanierService $panierService)
    {
        $this->panierService = $panierService;
    }

    #[Route('/panier', name: 'app_panier')]
    public function index(LivresRepository $livreRepository): Response
    {
        return $this->render('panier/index.html.twig', [
            'panier' => $this->panierService->getPanier(),
            'total' => $this->panierService->getTotal(),
            'livre_repository' => $livreRepository
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(int $id, Request $request): Response
    {
        $this->panierService->ajouter($id);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'totalItems' => $this->panierService->getNombreItems(),
                'total' => $this->panierService->getTotal(),
                'items' => $this->panierService->getPanier()
            ]);
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/retirer/{id}', name: 'app_panier_retirer', methods: ['POST'])]
    public function retirer(int $id, Request $request): Response
    {
        $this->panierService->retirer($id);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'totalItems' => $this->panierService->getNombreItems(),
                'total' => $this->panierService->getTotal(),
                'items' => $this->panierService->getPanier()
            ]);
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'])]
    public function supprimer(int $id, Request $request): Response
    {
        $this->panierService->supprimer($id);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'totalItems' => $this->panierService->getNombreItems(),
                'total' => $this->panierService->getTotal(),
                'items' => $this->panierService->getPanier()
            ]);
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/vider', name: 'app_panier_vider', methods: ['POST'])]
    public function vider(Request $request): Response
    {
        $this->panierService->vider();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        return $this->redirectToRoute('app_panier');
    }
}
