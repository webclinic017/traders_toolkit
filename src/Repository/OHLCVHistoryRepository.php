<?php

namespace App\Repository;

use App\Entity\OHLCVHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method OHLCVHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method OHLCVHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method OHLCVHistory[]    findAll()
 * @method OHLCVHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OHLCVHistoryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OHLCVHistory::class);
    }

    // /**
    //  * @return OHLCVHistory[] Returns an array of OHLCVHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OHLCVHistory
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
