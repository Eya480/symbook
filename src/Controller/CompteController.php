<?php

// src/Controller/CompteController.php
namespace App\Controller;

use App\Entity\Commande;
use App\Enum\EStatutCom;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
    #[IsGranted("ROLE_USER")]
    #[Route('/mon-compte/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos commandes.');
        }

        $queryBuilder = $this->entityManager->getRepository(Commande::class)
            ->createQueryBuilder('c')
            ->where('c.Utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.dateCommande', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('compte/mes_commandes.html.twig', [
            'commandes' => $pagination,
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
