<?php

namespace App\Repository;

use App\Entity\UserClickedFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class UserClickedFeedRepository.
 */
class UserClickedFeedRepository extends ServiceEntityRepository
{
    /**
     * UserClickedFeedRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserClickedFeed::class);
    }

    /**
     * Truncate the database table used for this entity.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function truncateTable(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL(UserClickedFeed::class);
        $connection->executeStatement($sql);
    }
}
