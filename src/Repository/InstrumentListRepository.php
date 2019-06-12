<?php

namespace App\Repository;

use App\Entity\InstrumentList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method InstrumentList|null find($id, $lockMode = null, $lockVersion = null)
 * @method InstrumentList|null findOneBy(array $criteria, array $orderBy = null)
 * @method InstrumentList[]    findAll()
 * @method InstrumentList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InstrumentListRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, InstrumentList::class);
    }

    // /**
    //  * @return InstrumentList[] Returns an array of InstrumentList objects
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
    public function findOneBySomeField($value): ?InstrumentList
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
