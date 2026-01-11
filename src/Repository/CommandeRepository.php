<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * Calcule le revenu total des commandes validées
     */
    public function countTotalRevenue(): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('SUM(c.montantTotal)')
            ->where('c.statut != :statut')
            ->setParameter('statut', 'ANNULEE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières commandes
     * @return Commande[]
     */
    public function findRecentOrders(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateCommande', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
