<?php

namespace App\Repository;

use App\Entity\SearchFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class SearchFeedRepository.
 */
class SearchFeedRepository extends ServiceEntityRepository
{
    /**
     * SearchFeedRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchFeed::class);
    }

    /**
     * Truncate the database table used for this entity.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function truncateTable(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL(SearchFeed::class);
        $connection->executeStatement($sql);
    }
}
