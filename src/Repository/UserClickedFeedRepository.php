<?php

namespace App\Repository;

use App\Entity\UserClickedFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserClickedFeed|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserClickedFeed|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserClickedFeed[]    findAll()
 * @method UserClickedFeed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserClickedFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserClickedFeed::class);
    }

    /**
     * Truncate the database table used for this entity.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function truncateTable()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL(UserClickedFeed::class);
        $connection->executeStatement($sql);
    }
}
