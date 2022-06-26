<?php

namespace App\Service;

use App\Entity\SearchFeed;
use App\Repository\SearchFeedRepository;
use Doctrine\ORM\EntityManagerInterface;
use ForceUTF8\Encoding;

/**
 * Class ParseSearchFeedService.
 */
class ParseSearchFeedService
{
    private string $destinationDirectory;
    private SearchFeedRepository $searchFeedRepos;
    private EntityManagerInterface $em;
    private CsvReaderService $CsvReader;

    /**
     * ParseSearchFeedService constructor.
     *
     * @param string $bindDestinationDirectory
     * @param EntityManagerInterface $entityManager
     * @param SearchFeedRepository $searchFeedRepository
     * @param CsvReaderService $CsvReaderService
     */
    public function __construct(string $bindDestinationDirectory, EntityManagerInterface $entityManager, SearchFeedRepository $searchFeedRepository, CsvReaderService $CsvReaderService)
    {
        $this->destinationDirectory = $bindDestinationDirectory;
        $this->em = $entityManager;
        $this->searchFeedRepos = $searchFeedRepository;
        $this->CsvReader = $CsvReaderService;
    }

    /**
     * Parse CSV file with search query information.
     *
     * Note the function yield for every 500 rows parsed to provide feedback on the parsing process.
     *
     * @param string $filename
     *   If provided the file will be used as input else file will be downloaded
     *
     * @return \Generator
     *   Yield for every 500 rows
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function parse(string $filename): \Generator
    {
        $rowsCount = 0;
        $rowsInserted = 0;
        $rowsUpdated = 0;

        // Bookkeeping between batches.
        $entities = [];

        $iterator = $this->CsvReader->read($filename);
        foreach ($iterator as $line) {
            $searchYear = (int) $line[0];
            $searchWeek = (int) $line[1];

            ++$rowsCount;

            // Yield progress.
            if (0 == $rowsCount % 500) {
                yield ['processed' => $rowsCount, 'inserted' => $rowsInserted, 'updated' => $rowsUpdated];
            }

            if ($this->isFromPeriod($searchYear, $searchWeek)) {
                $searchKey = htmlspecialchars_decode($line[2]);
                $search_count = (int) $line[3];

                // We exclude complex search strings.
                if ($this->isValid($searchKey)) {
                    $entities[$searchKey] = array_key_exists($searchKey, $entities) ? $entities[$searchKey] : $this->searchFeedRepos->findOneBy(['search' => $searchKey]);
                    if (is_null($entities[$searchKey])) {
                        $entities[$searchKey] = new SearchFeed();
                        $entities[$searchKey]->setYear($searchYear);
                        $entities[$searchKey]->setWeek($searchWeek);
                        $entities[$searchKey]->setSearch($searchKey);

                        $this->em->persist($entities[$searchKey]);
                        ++$rowsInserted;
                    }
                    else {
                        ++$rowsUpdated;
                    }
                    $entities[$searchKey]->incriminateLongPeriod($search_count);

                    if ($this->isFromPeriod($searchYear, $searchWeek, 4)) {
                        $entities[$searchKey]->incriminateShortPeriod($search_count);
                    }

                    // Make it stick for every 5000 entities loaded into memory.
                    if (0 === count($entities) % 5000) {
                        $this->em->flush();
                        $this->em->clear();

                        $entities = [];
                        gc_collect_cycles();
                    }
                }
            }
        }

        // Make it stick.
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * Write that parsed data into CSV output file ordered by most searches.
     *
     * @param int $rows
     *   Number of rows to output
     * @param string $filename
     *   The filename to write to in the public folder
     */
    public function writeFile(int $rows = 5000, string $filename = 'searchdata.csv'): void
    {
        $file = fopen($this->destinationDirectory.'/'.$filename, 'w');

        $query = $this->em->createQueryBuilder()
            ->select('s')
            ->from(SearchFeed::class, 's')
            ->orderBy('s.longPeriod', 'DESC')
            ->setMaxResults($rows)
            ->getQuery();

        $iterable = $query->toIterable();
        foreach ($iterable as $entity) {
            // Force encoding to UTF8 for the search string.
            $search = Encoding::toUTF8($entity->getSearch());

            $values = [$search, $entity->getLongPeriod(), $entity->getShortPeriod()];
            fputcsv($file, $values);
        }

        fclose($file);
    }

    /**
     * Reset the database table (truncate it).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function reset(): void
    {
        $this->searchFeedRepos->truncateTable();
    }

    /**
     * Check if smart search record is within look-back.
     *
     * @param int $year
     *   Year number
     * @param int $week
     *   Week number
     * @param int $period
     *   Weeks from now that the year and week should be with in
     *
     * @return bool
     */
    private function isFromPeriod(int $year, int $week, int $period = 52): bool
    {
        $date = new \DateTime();
        $nowYear = (int) $date->format('Y');
        $nowWeek = (int) $date->format('W');
        if ($year == $nowYear && $nowWeek - $period <= $week) {
            return true;
        } elseif (($year == $nowYear - 1) && $nowWeek <= $period) {
            if ($week >= (52 - $period + $nowWeek)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a valid search string.
     *
     * Use to filter out search that are most often made by professionals and don't need to be addresses by the service.
     * Also exclude numeric search faust/isbn searches and other strange searches.
     *
     * @param string $key
     *   The search key
     *
     * @return bool
     *   The result of the validation
     */
    private function isValid(string $key): bool
    {
        if (false !== strpos($key, '=') || false !== strpos($key, '(') || false !== strpos($key, '*')) {
            return false;
        }

        $val = str_replace(',', '', $key);
        if (is_numeric($val)) {
            return false;
        }

        return true;
    }
}
