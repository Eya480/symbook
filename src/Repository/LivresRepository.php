<?php
// src/Repository/LivresRepository.php (mise à jour)
namespace App\Repository;

use App\Entity\Livres;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<Livres>
 */
class LivresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livres::class);
    }

    /**
     * Trouve les livres les plus vendus dans une période donnée
     */
    public function findMostSoldBookInPeriod(DateTime $dateDebut, DateTime $dateFin, int $limit = 5)
    {
        return $this->getEntityManager()
            ->createQuery('
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
            ->setMaxResults($limit)
            ->getResult();
    }
}