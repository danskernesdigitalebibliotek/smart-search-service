<?php

namespace App\Repository;

use App\Entity\UserClickedFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
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
     * @throws Exception
     */
    public function truncateTable(): void
    {
        $table = $this->getClassMetadata()->getTableName();
        $connection = $this->getEntityManager()->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL($table);
        $connection->executeStatement($sql);
    }
}
