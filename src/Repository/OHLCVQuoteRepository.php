<?php

namespace App\Repository;

use App\Entity\OHLCVQuote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method OHLCVQuote|null find($id, $lockMode = null, $lockVersion = null)
 * @method OHLCVQuote|null findOneBy(array $criteria, array $orderBy = null)
 * @method OHLCVQuote[]    findAll()
 * @method OHLCVQuote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OHLCVQuoteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OHLCVQuote::class);
    }

    // /**
    //  * @return OHLCVQuote[] Returns an array of OHLCVQuote objects
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
    public function findOneBySomeField($value): ?OHLCVQuote
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
