<?php

namespace App\Service;

use App\Entity\UserClickedFeed;
use App\Repository\UserClickedFeedRepository;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ParseUserClickedService.
 */
class ParseUserClickedService
{
    private string $projectDir;
    private string $destinationDirectory;
    private EntityManagerInterface $em;
    private UserClickedFeedRepository $userClickedRepos;

    /**
     * ParseUserClickedService constructor.
     *
     * @param string $bindProjectDir
     * @param string $bindDestinationDirectory
     * @param EntityManagerInterface $entityManager
     * @param UserClickedFeedRepository $UserClickedFeedRepository
     */
    public function __construct(string $bindProjectDir, string $bindDestinationDirectory, EntityManagerInterface $entityManager, UserClickedFeedRepository $UserClickedFeedRepository)
    {
        $this->projectDir = $bindProjectDir;
        $this->destinationDirectory = $bindDestinationDirectory;
        $this->em = $entityManager;
        $this->userClickedRepos = $UserClickedFeedRepository;
    }

    /**
     * Parse CSV file with user clicked information.
     *
     * Note the function yield for every 500 rows parsed to provide feedback on the parsing process.
     *
     * @param string $filename
     *   If provided the file will be used as input else file will be downloaded
     *
     * @return \Generator
     *   Yield for every 500 rows
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parse(string $filename): \Generator
    {
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->setFieldDelimiter(';');
        $reader->open($filename);

        $rowsCount = 0;
        $rowsInserted = 0;

        // Book keeping between batches.
        $entities = [];

        $this->em->getConnection()->beginTransaction();

        /* @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            /* @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                ++$rowsCount;

                // Skip first row which is headers.
                if (1 === $rowsCount) {
                    continue;
                }
                $page = $row->getCellAtIndex(1)->getValue();

                // Yield progress).
                if (0 == $rowsCount % 500) {
                    yield [ 'processed' => $rowsCount, 'inserted' => $rowsInserted ];
                }

                // Find the linked data-well post id (PID).
                $pid = $this->getPidFromPage($page);
                if (!empty($pid)) {
                    $rowsInserted++;

                    $searchKey = $row->getCellAtIndex(0)->getValue();
                    $clicks = (int) $row->getCellAtIndex(2)->getValue();

                    $entities[$searchKey] = array_key_exists($searchKey, $entities) ? $entities[$searchKey] : $this->userClickedRepos->findOneBy([
                        'search' => $searchKey,
                        'pid' => $pid,
                    ]);
                    if (is_null($entities[$searchKey])) {
                        $entities[$searchKey] = new UserClickedFeed();
                        $entities[$searchKey]->setPid($pid);
                        $entities[$searchKey]->setSearch($searchKey);
                        $this->em->persist($entities[$searchKey]);
                    }
                    $entities[$searchKey]->incriminateClicks($clicks);

                    // Make it stick for every 500 rows.
                    if (0 === $rowsInserted % 5000) {
                        $this->em->flush();
                        $this->em->getConnection()->commit();
                        $this->em->clear();

                        $entities = [];
                        gc_collect_cycles();

                        // Start new transaction for the next batch.
                        $this->em->getConnection()->beginTransaction();
                    }
                }
            }

            // Make it stick.
            $this->em->flush();
            $this->em->getConnection()->commit();
        }

        $reader->close();
    }

    /**
     * Write auto data with serialized object.
     *
     * @param string $filename
     *   The file name to store the serialized data object in public folder
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function writeFile(string $filename = 'autodata.txt'): void
    {
        // This is done with raw SQL statements as the query build will not accept the sub-query.
        $subQuery = '(SELECT pid, search, sum(clicks) AS clicks FROM user_clicked_feed u GROUP BY search, pid)';
        $query = 'SELECT * FROM '.$subQuery.' WHERE clicks > 2 ORDER BY search ASC, clicks DESC';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $iterable = $stmt->iterateAssociative();
        $data = [];
        foreach ($iterable as $row) {
            // As the data is ordered by click for each searches we can limit it to the 5 object pr. search as we known
            // that the data is sorted correctly.
            if (isset($data[$row['search']]) && count($data[$row['search']]) >= 5) {
                // If 5 objects (PIDs) for an given search have been found skip the rest.
                continue;
            }

            !isset($data[$row['search']]) ?? $data[$row['search']] = [];
            $data[$row['search']][$row['pid']] = $row['clicks'];
        }

        $data = serialize($data);
        file_put_contents($this->destinationDirectory.'/'.$filename, $data);
    }

    /**
     * Reset the database table (truncate it).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function reset(): void
    {
        $this->userClickedRepos->truncateTable();
    }

    /**
     * Try finding PID in the page clicked information.
     *
     * Also filter out eReolen links (why, don't know).
     *
     * @param string $page
     *   Page part of the data from the source file
     *
     * @return string
     *   The pid if found else the empty string
     */
    private function getPidFromPage(string $page): string
    {
        if (!(false !== strpos($page, 'ereolen'))) {
            preg_match("%ting\.(collection|object)\.(.+)%", $page, $matches);
            if ($matches && isset($matches[2])) {
                return $matches[2];
            }
        }

        return '';
    }
}
