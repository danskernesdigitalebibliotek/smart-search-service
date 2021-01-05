<?php

namespace App\Repository;

use App\Entity\SearchFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SearchFeed|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchFeed|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchFeed[]    findAll()
 * @method SearchFeed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchFeed::class);
    }

    // /**
    //  * @return SearchFeed[] Returns an array of SearchFeed objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SearchFeed
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
