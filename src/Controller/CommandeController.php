<?php

namespace App\Controller;


use Stripe\Stripe;
use App\Entity\Commande;
use App\Enum\EStatutCom;
use Stripe\PaymentIntent;
use App\Form\CommandeType;
use Stripe\Checkout\Session;
use App\Entity\LigneCommande;
use App\Service\PanierService;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
            'stripePublicKey' => $this->getParameter('STRIPE_PUBLIC_KEY')
        ]);
    }


    #[Route('/admin/commandes', name: 'admin_commandes')]
    #[IsGranted("ROLE_ADMIN")]
    public function gestionCommandes(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response
    {
        // Filtres et pagination
        $status = $request->query->get('status', 'all');

        // Dans votre contrôleur ou repository
        $qb = $em->getRepository(Commande::class)->createQueryBuilder('c')
            ->leftJoin('c.Utilisateur', 'u')
            ->addSelect('u');

        // Filtre par statut
        if ($status !== 'all') {
            $qb->where('c.status = :status')
                ->setParameter('status', $status);
        }

        // Tri par date récente
        $qb->orderBy('c.dateCommande', 'DESC');

        $pagination = $paginator->paginate(
            $qb, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            10 /* limit per page */
        );

        return $this->render('admin/index.html.twig', [
            'pagination' => $pagination,
            'status_filter' => $status,
            'statuts' => EStatutCom::cases()
        ]);
    }

    #[Route('/admin/commande/valider/{id}', name: 'admin_commande_valider')]
    #[IsGranted("ROLE_ADMIN")]
    public function validerCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $commande->setStatus(EStatutCom::PAID);
        $em->flush();

        $this->addFlash('success', 'Commande validée avec succès');
        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/admin/commande/expedier/{id}', name: 'admin_commande_expedier')]
    #[IsGranted("ROLE_ADMIN")]
    public function expedierCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $commande->setStatus(EStatutCom::SHIPPED);
        $em->flush();

        $this->addFlash('success', 'Commande expédiée avec succès.');
        return $this->redirectToRoute('admin_commandes');
    }

    #[Route('/admin/commande/modifier/{id}', name: 'admin_commande_modifier')]
    #[IsGranted("ROLE_ADMIN")]
    public function modifierCommande(Request $request, Commande $commande, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commande modifiée avec succès.');
            return $this->redirectToRoute('admin_commandes');
        }

        return $this->render('admin/modifier.html.twig', [
            'form' => $form->createView(),
            'commande' => $commande,
        ]);
    }

    public function confirmer(
        Request $request,
        PanierService $panierService,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Vérifier que l'utilisateur est authentifié
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user) {
            error_log('CommandeController: Utilisateur non authentifié');
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Vérifier le panier
        try {
            $panier = $panierService->getPanierComplet();
        } catch (\Exception $e) {
            error_log('CommandeController: Erreur PanierService::getPanierComplet: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la récupération du panier'], 500);
        }

        if (empty($panier)) {
            return new JsonResponse(['error' => 'Votre panier est vide'], 400);
        }

        // Récupération des données du formulaire
        $adresseLivraison = $request->request->get('adresseLivraison');
        $modePaiement = $request->request->get('modePaiement');

        if (!$adresseLivraison) {
            return new JsonResponse(['error' => 'Veuillez sélectionner une adresse de livraison'], 400);
        }

        // Vérification du stock
        foreach ($panier as $item) {
            $livre = $item['livre'];
            if ($livre->getStock() < $item['quantite']) {
                return new JsonResponse([
                    'error' => sprintf(
                        'Stock insuffisant pour "%s" (stock disponible: %d)',
                        $livre->getTitre(),
                        $livre->getStock()
                    )
                ], 400);
            }
        }

        // Calcul du total
        try {
            $total = $panierService->getTotal();
        } catch (\Exception $e) {
            error_log('CommandeController: Erreur PanierService::getTotal: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors du calcul du total'], 500);
        }

        $fraisLivraison = $total >= 300 ? 0 : 8;
        $total += $fraisLivraison;

        if ($total <= 0) {
            error_log('CommandeController: Total non valide: ' . $total);
            return new JsonResponse(['error' => 'Montant total non valide'], 400);
        }

        // Gestion selon le mode de paiement
        if ($modePaiement === 'carte') {
            // Paiement par carte avec Stripe Checkout
            $stripeSecretKey = $this->getParameter('STRIPE_SECRET_KEY');
            if (empty($stripeSecretKey)) {
                error_log('CommandeController: Clé secrète Stripe manquante');
                return new JsonResponse(['error' => 'Configuration Stripe invalide'], 500);
            }

            Stripe::setApiKey($stripeSecretKey);

            try {
                // Créer la commande en statut PENDING
                $commande = $this->creerCommande($em, $panierService, $adresseLivraison, $total, EStatutCom::PENDING);

                // Créer une session Stripe Checkout
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'eur',
                            'unit_amount' => $total * 100, // Stripe utilise des centimes
                            'product_data' => [
                                'name' => 'Commande #' . $commande->getReference(),
                            ],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => 'http://127.0.0.1:8000'.$this->generateUrl('app_commande_confirmation', ['id' => $commande->getId()], true),
                    'cancel_url' => 'http://127.0.0.1:8000'.$this->generateUrl('app_commande', [], true),
                    'metadata' => [
                        'commande_id' => $commande->getId(),
                        'user_id' => $user->getUserIdentifier()
                    ]
                ]);

                // Sauvegarder l'ID de la session Stripe dans la commande
                $commande->setStripePaymentId($session->id);
                $em->flush();

                // Retourner l'ID de la session pour la redirection côté client
                return new JsonResponse(['sessionId' => $session->id]);
            } catch (\Exception $e) {
                error_log('CommandeController: Erreur Stripe: ' . $e->getMessage());
                return new JsonResponse(['error' => 'Erreur lors du paiement: ' . $e->getMessage()], 500);
            }
        } else {
            // Paiement à la livraison 
            try {
                $commande = $this->creerCommande($em, $panierService, $adresseLivraison, $total, EStatutCom::PAYMENT_PENDING);
                $this->sendConfirmationEmail($commande, $mailer);
                return new JsonResponse([
                    'redirect' => $this->generateUrl('app_commande_confirmation', ['id' => $commande->getId()])
                ]);
            } catch (\Exception $e) {
                error_log('CommandeController: Erreur création commande non-Stripe: ' . $e->getMessage());
                return new JsonResponse(['error' => 'Erreur lors de la création de la commande: ' . $e->getMessage()], 500);
            }
        }
    }

    #[Route('/commande/verifier-paiement/{id}', name: 'app_commande_verifier_paiement')]
    public function verifierPaiement(Commande $commande, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getStripePaymentId()) {
            Stripe::setApiKey($this->getParameter('STRIPE_SECRET_KEY'));

            try {
                $session = Session::retrieve($commande->getStripePaymentId());

                if ($session->payment_status === 'paid') {
                    $commande->setStatus(EStatutCom::PAID);
                    $em->flush();
                    $this->sendConfirmationEmail($commande, $mailer);
                    return $this->redirectToRoute('app_commande_confirmation', ['id' => $commande->getId()]);
                }
            } catch (\Exception $e) {
                error_log('CommandeController: Erreur vérification Stripe: ' . $e->getMessage());
            }
        }

        // Si le paiement n'est pas confirmé, rediriger vers la page de commande
        return $this->redirectToRoute('app_commande');
    }


    private function creerCommande(EntityManagerInterface $em, PanierService $panierService, $adresseLivraison, $total, $status)
    {
        try {
            $commande = new Commande();
            $commande->setUtilisateur($this->getUser());
            $commande->setDateCommande(new \DateTime());
            $commande->setDateCreation(new \DateTime()); // Fix: Set DateCreation
            $commande->setStatus($status);
            $commande->setReference($this->generateReference());
            $commande->setAdresseLivraison($adresseLivraison);
            $commande->setMontantTotal($total);

            $em->persist($commande);

            foreach ($panierService->getPanierComplet() as $item) {
                $ligneCommande = new LigneCommande();
                $ligneCommande->setCommande($commande);
                $ligneCommande->setLivre($item['livre']);
                $ligneCommande->setQte($item['quantite']);
                $ligneCommande->setRemise(0);

                // Mise à jour du stock
                $item['livre']->setStock($item['livre']->getStock() - $item['quantite']);
                $em->persist($item['livre']); // Persist stock changes

                $em->persist($ligneCommande);
            }

            $em->flush();
            $panierService->vider();

            return $commande;
        } catch (\Exception $e) {
            error_log('CommandeController::creerCommande: Erreur: ' . $e->getMessage());
            throw $e;
        }
    }
    #[Route('/admin/commande/annuler/{id}', name: 'admin_commande_annuler')]
    #[IsGranted("ROLE_ADMIN")]
    public function annulerCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $commande->setStatus(EStatutCom::CANCELLED);
        $em->flush();

        $this->addFlash('success', 'Commande annulée avec succès');
        return $this->redirectToRoute('admin_commandes');
    }

    #[IsGranted("ROLE_USER")]
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

    #[Route('/commande/details/{id}', name: 'app_commande_details')]
    public function showDetails(Commande $commande): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // Vérifie que l'utilisateur est bien le propriétaire de la commande
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        return $this->render('commande/details.html.twig', [
            'commande' => $commande,
        ]);
    }

    private function sendConfirmationEmail(Commande $commande, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('eyaelgharbi889@gmail.com')
            ->to($commande->getUtilisateur()->getEmail())
            ->subject('Confirmation de votre commande #' . $commande->getReference())
            ->html($this->renderView(
                'commande/confirmation_email.html.twig',
                ['commande' => $commande]
            ));

        $mailer->send($email);
    }
}
