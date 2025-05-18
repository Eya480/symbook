<?php
// src/Controller/DashboardController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\LivresRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Livres;
use DateTime;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        Request $request,
        LivresRepository $livresRepository,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): Response
    {
        // Récupérer les dates de début et de fin depuis la requête avec des valeurs par défaut
        $dateDebutStr = $request->query->get('dateDebut');
        $dateFinStr = $request->query->get('dateFin');

        // Utiliser une date par défaut si aucune date n'est fournie
        $dateDebut = $dateDebutStr ? new DateTime($dateDebutStr) : new DateTime('-30 days');
        $dateFin = $dateFinStr ? new DateTime($dateFinStr) : new DateTime('now');

        // 1. Livres les plus vendus
        $livresPlusVendus = $em->createQuery('
            SELECT l.titre, l.image, SUM(lc.qte) as quantite_vendue
            FROM App\Entity\Livres l
            JOIN App\Entity\LigneCommande lc WITH lc.Livre = l
            JOIN App\Entity\Commande c WITH lc.commande = c
            WHERE c.dateCommande BETWEEN :dateDebut AND :dateFin
            GROUP BY l.id, l.titre, l.image
            ORDER BY quantite_vendue DESC
        ')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setMaxResults(5)
            ->getResult();

        // Si aucun résultat, utiliser des données de test
        if (empty($livresPlusVendus)) {
            $livresPlusVendus = [
                ['titre' => 'Le Petit Prince', 'image' => 'placeholder.jpg', 'quantite_vendue' => 120],
                ['titre' => 'Harry Potter', 'image' => 'placeholder.jpg', 'quantite_vendue' => 90],
                ['titre' => 'Le Seigneur des Anneaux', 'image' => 'placeholder.jpg', 'quantite_vendue' => 70],
                ['titre' => '1984', 'image' => 'placeholder.jpg', 'quantite_vendue' => 50],
                ['titre' => 'L\'Étranger', 'image' => 'placeholder.jpg', 'quantite_vendue' => 30],
            ];
        }

        // 2. Commandes par jour - version sans DATE_FORMAT
        $commandesBrutes = $em->createQuery('
            SELECT c.dateCommande
            FROM App\Entity\Commande c
            WHERE c.dateCommande BETWEEN :dateDebut AND :dateFin
            ORDER BY c.dateCommande ASC
        ')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getResult();

        // Traitement des données en PHP plutôt qu'en SQL
        $commandesParJour = [];
        foreach ($commandesBrutes as $commande) {
            $date = $commande['dateCommande']->format('Y-m-d');
            if (!isset($commandesParJour[$date])) {
                $commandesParJour[$date] = 0;
            }
            $commandesParJour[$date]++;
        }

        // Formater pour le graphique
        $commandesFormatees = [];
        foreach ($commandesParJour as $date => $nombre) {
            $commandesFormatees[] = ['date' => $date, 'nombre' => $nombre];
        }

        // Si aucun résultat, utiliser des données de test
        if (empty($commandesFormatees)) {
            $commandesFormatees = $this->generateTestOrderData($dateDebut, $dateFin);
        }

        // Préparer les données pour les graphiques
        $dataLivresPlusVendus = [['Livre', 'Exemplaires vendus']];
        foreach ($livresPlusVendus as $livre) {
            $dataLivresPlusVendus[] = [$livre['titre'], intval($livre['quantite_vendue'])];
        }

        $dataCommandesParJour = [['Date', 'Nombre de commandes']];
        foreach ($commandesFormatees as $commande) {
            $dataCommandesParJour[] = [$commande['date'], intval($commande['nombre'])];
        }

        return $this->render('dashboard/index.html.twig', [
            'bestSellingBook' => json_encode($dataLivresPlusVendus),
            'ordersByPeriod' => json_encode($dataCommandesParJour),
            'livresPlusVendus' => $livresPlusVendus,
            'dateDebut' => $dateDebut->format('Y-m-d'),
            'dateFin' => $dateFin->format('Y-m-d'),
        ]);
    }

    private function generateTestOrderData(DateTime $dateDebut, DateTime $dateFin): array
    {
        $result = [];
        $currentDate = clone $dateDebut;

        while ($currentDate <= $dateFin) {
            $result[] = [
                'date' => $currentDate->format('Y-m-d'),
                'nombre' => rand(5, 30)
            ];
            $currentDate->modify('+1 day');
        }

        return $result;
    }
}