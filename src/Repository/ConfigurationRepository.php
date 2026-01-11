<?php

namespace App\Repository;

use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configuration>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    //    /**
    //     * @return Configuration[] Returns an array of Configuration objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Get a configuration value by its key
     */
    public function getValue(string $key, ?string $default = null): ?string
    {
        $config = $this->findOneBy(['settingKey' => $key]);
        return $config ? $config->getSettingValue() : $default;
    }

    /**
     * Get stock alert threshold (default: 5)
     */
    public function getStockAlertThreshold(): int
    {
        return (int) $this->getValue('stock_alert_threshold', '5');
    }
}
