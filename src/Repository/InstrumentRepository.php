<?php

namespace App\Repository;

use App\Entity\Instrument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Instrument|null find($id, $lockMode = null, $lockVersion = null)
 * @method Instrument|null findOneBy(array $criteria, array $orderBy = null)
 * @method Instrument[]    findAll()
 * @method Instrument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InstrumentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Instrument::class);
    }

    /**
     * Deletes price history between dates
     * @param DateTime $fromDate
     * @param DateTime $toDate
     * @param DateInterval entries for which time period supposed to be deleted
     * @param string $provider if no provider supplied price records for all providers will be removed
     */
    // public function deleteHistory($fromDate, $toDate, $interval, $provider = null)
    // {
        // $qb = $this->createQueryBuilder();
        // $expr = $this->getExpressionBuilder();
        // $qb->select()->from('instruments', 'i')->where($expr->eq('i.symbol', $this->getSymbol()));
        // $query = $qb->getQuery();

        // $result = $query->getResult();
        // var_dump($result); 
        // ;
    // }

    // /**
    //  * @return Instrument[] Returns an array of Instrument objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Instrument
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
