<?php

namespace App\Service;

use App\Entity\UserClickedFeed;
use App\Repository\UserClickedFeedRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use ForceUTF8\Encoding;

/**
 * Class ParseUserClickedService.
 */
class ParseUserClickedService
{
    /**
     * ParseUserClickedService constructor.
     *
     * @param string $destinationDirectory
     * @param EntityManagerInterface $em
     * @param UserClickedFeedRepository $userClickedRepos
     * @param CsvReaderService $CsvReader
     */
    public function __construct(
        private readonly string $destinationDirectory,
        private readonly EntityManagerInterface $em,
        private readonly UserClickedFeedRepository $userClickedRepos,
        private readonly CsvReaderService $CsvReader
    ) {
    }

    /**
     * Parse CSV file with user clicked information.
     *
     * Note the function yield for every 500 rows parsed to provide feedback on the parsing process.
     *
     * @param string $filename
     *   If provided the file will be used as input else file will be downloaded
     *
     *   Yield for every 500 rows
     *
     * @throws Exception
     */
    public function parse(string $filename): \Generator
    {
        $rowsCount = 0;
        $rowsInserted = 0;
        $rowsUpdated = 0;

        // Bookkeeping between batches.
        $entities = [];

        $this->em->getConnection()->beginTransaction();

        $iterator = $this->CsvReader->read($filename, ';');
        foreach ($iterator as $line) {
            ++$rowsCount;

            // Skip first row which is headers.
            if (1 === $rowsCount) {
                continue;
            }
            $page = $line[1];

            // Yield progress.
            if (0 == $rowsCount % 500) {
                yield ['processed' => $rowsCount, 'inserted' => $rowsInserted, 'updated' => $rowsUpdated];
            }

            // We need to test if there was any data as the input is very unstable.
            if (null !== $page) {
                // Find the linked data-well post id (PID).
                $pid = $this->getPidFromPage($page);
                if (!empty($pid)) {
                    $searchKey = htmlspecialchars_decode((string) $line[0]);
                    $clicks = (int) $line[2];

                    $entities[$searchKey] = array_key_exists($searchKey, $entities) ? $entities[$searchKey] : $this->userClickedRepos->findOneBy([
                        'search' => $searchKey,
                        'pid' => $pid,
                    ]);
                    if (is_null($entities[$searchKey])) {
                        $entities[$searchKey] = new UserClickedFeed();
                        $entities[$searchKey]->setPid($pid);
                        $entities[$searchKey]->setSearch($searchKey);

                        $this->em->persist($entities[$searchKey]);
                        ++$rowsInserted;
                    } else {
                        ++$rowsUpdated;
                    }
                    $entities[$searchKey]->incriminateClicks($clicks);

                    // Make it stick for every 500 rows.
                    if (0 === count($entities) % 500) {
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
        }

        // Make it stick.
        $this->em->flush();
        $this->em->getConnection()->commit();
    }

    /**
     * Write auto data with serialized object.
     *
     * @param string $filename
     *   The file name to store the serialized data object in public folder
     *
     * @throws Exception
     */
    public function writeFile(string $filename = 'autodata.txt'): void
    {
        // This is done with raw SQL statements as the query build will not accept the sub-query.
        $subQuery = '(SELECT pid, search, sum(clicks) AS clicks FROM user_clicked_feed u GROUP BY search, pid) as ucf';
        $query = 'SELECT ucf.* FROM '.$subQuery.' WHERE clicks > 2 ORDER BY search ASC, clicks DESC';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->executeStatement();
        $iterable = $stmt->executeQuery()->iterateAssociative();

        $data = [];
        foreach ($iterable as $row) {
            // Force encoding to UTF8 for the search string.
            $row['search'] = Encoding::toUTF8($row['search']);

            // As the data is ordered by click for each search's we can limit it to the 5 object pr. search as we knew
            // that the data is sorted correctly.
            if (isset($data[$row['search']]) && count($data[$row['search']]) >= 5) {
                // If 5 objects (PIDs) for a given search have been found skip the rest.
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
     * @throws Exception
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
     *   The pid if found else the empty string
     */
    private function getPidFromPage(string $page): string
    {
        if (!(str_contains($page, 'ereolen'))) {
            preg_match("%ting\.(collection|object)\.(.+)%", $page, $matches);
            if ($matches && isset($matches[2])) {
                return $matches[2];
            }
        }

        return '';
    }
}
