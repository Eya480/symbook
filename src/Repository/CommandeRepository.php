<?php
// src/Repository/CommandeRepository.php (mise à jour)
namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Trouve le nombre de commandes par jour dans une période donnée
     */
    public function findOrderCountByDay(DateTime $dateDebut, DateTime $dateFin): array
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT DATE_FORMAT(c.dateCommande, \'%Y-%m-%d\') as date, COUNT(c.id) as nombre
                FROM App\Entity\Commande c
                WHERE c.dateCommande BETWEEN :dateDebut AND :dateFin
                GROUP BY date
                ORDER BY date ASC
            ')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getResult();
    }
}