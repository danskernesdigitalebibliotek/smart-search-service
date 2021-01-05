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

    /**
     * Truncate the database table used for this entity.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function truncateTable() {
        $connection = $this->getEntityManager()->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL(SearchFeed::class);
        $connection->executeStatement($sql);
    }
}
