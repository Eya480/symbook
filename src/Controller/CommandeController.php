<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Enum\EStatutCom;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandeController extends AbstractController
{
    #[Route('/commande', name: 'app_commande')]
    public function index(PanierService $panierService): Response
    {
        $panier = $panierService->getPanierComplet();

        if (empty($panier)) {
            $this->addFlash('warning', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        return $this->render('commande/index.html.twig', [
            'items' => $panier,
            'total' => $panierService->getTotal(),
        ]);
    }

    #[Route('/commande/confirmer', name: 'app_commande_confirmer', methods: ['POST'])]
    public function confirmer(
        Request $request,
        PanierService $panierService,
        EntityManagerInterface $em
    ): Response {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $panier = $panierService->getPanierComplet();

        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_panier');
        }

        // Récupérer l'adresse de livraison
        $adresseLivraison = $request->request->get('adresseLivraison');
        if (empty($adresseLivraison)) {
            $this->addFlash('error', 'L\'adresse de livraison est requise');
            return $this->redirectToRoute('app_commande');
        }

        // Vérification du stock
        foreach ($panier as $item) {
            $livre = $item['livre'];
            if ($livre->getStock() < $item['quantite']) {
                $this->addFlash('error', sprintf(
                    'Stock insuffisant pour "%s" (stock disponible: %d)',
                    $livre->getTitre(),
                    $livre->getStock()
                ));
                return $this->redirectToRoute('app_panier');
            }
        }

        // Création de la commande
        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->setDateCommande(new \DateTime());
        $commande->setDateCreation(new \DateTime());
        $commande->setStatus(EStatutCom::PENDING);
        $commande->setReference($this->generateReference());
        $commande->setAdresseLivraison($adresseLivraison);

        $em->persist($commande);

        // Ajout des lignes de commande
        $total = 0;
        foreach ($panier as $item) {
            $livre = $item['livre'];
            $quantite = $item['quantite'];
            $prixUnitaire = $livre->getPrix();
            $sousTotal = $prixUnitaire * $quantite;

            $ligneCommande = new LigneCommande();
            $ligneCommande->setCommande($commande);
            $ligneCommande->setLivre($livre);
            $ligneCommande->setQte($quantite);
            $ligneCommande->setRemise(0); // Par défaut, pas de remise

            // Mise à jour du stock
            $livre->setStock($livre->getStock() - $quantite);

            $em->persist($ligneCommande);
            $total += $sousTotal;
        }

        $commande->setMontantTotal($total);

        try {
            $em->flush();
            $panierService->vider();

            $this->addFlash('success', 'Votre commande a été confirmée avec succès');
            return $this->redirectToRoute('app_commande_confirmation', [
                'id' => $commande->getId(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la confirmation de votre commande');
            return $this->redirectToRoute('app_panier');
        }
    }

    #[Route('/commande/confirmation/{id}', name: 'app_commande_confirmation')]
    public function confirmation(Commande $commande): Response
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }

    private function generateReference(): string
    {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}