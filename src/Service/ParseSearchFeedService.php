<?php

namespace App\Service;

use App\Entity\SearchFeed;
use App\Repository\SearchFeedRepository;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use ForceUTF8\Encoding;

/**
 * Class ParseSearchFeedService.
 */
class ParseSearchFeedService
{
    private string $projectDir;
    private string $destinationDirectory;
    private SearchFeedRepository $searchFeedRepos;
    private EntityManagerInterface $em;

    /**
     * ParseSearchFeedService constructor.
     *
     * @param string $bindProjectDir
     * @param string $bindDestinationDirectory
     * @param EntityManagerInterface $entityManager
     * @param SearchFeedRepository $searchFeedRepository
     */
    public function __construct(string $bindProjectDir, string $bindDestinationDirectory, EntityManagerInterface $entityManager, SearchFeedRepository $searchFeedRepository)
    {
        $this->projectDir = $bindProjectDir;
        $this->destinationDirectory = $bindDestinationDirectory;
        $this->em = $entityManager;
        $this->searchFeedRepos = $searchFeedRepository;
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
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function parse(string $filename): \Generator
    {
        $reader = ReaderEntityFactory::createCSVReader();
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
                $searchYear = (int) $row->getCellAtIndex(0)->getValue();
                $searchWeek = (int) $row->getCellAtIndex(1)->getValue();

                ++$rowsCount;

                // Yield progress.
                if (0 == $rowsCount % 500) {
                    yield ['processed' => $rowsCount, 'inserted' => $rowsInserted];
                }

                if ($this->isFromPeriod($searchYear, $searchWeek)) {
                    $searchKey = $row->getCellAtIndex(2)->getValue();
                    $search_count = (int) $row->getCellAtIndex(3)->getValue();

                    // We exclude complex search strings.
                    if ($this->isValid($searchKey)) {
                        ++$rowsInserted;

                        $entities[$searchKey] = array_key_exists($searchKey, $entities) ? $entities[$searchKey] : $this->searchFeedRepos->findOneBy(['search' => $searchKey]);
                        if (is_null($entities[$searchKey])) {
                            $entities[$searchKey] = new SearchFeed();
                            $entities[$searchKey]->setYear($searchYear);
                            $entities[$searchKey]->setWeek($searchWeek);
                            $entities[$searchKey]->setSearch($searchKey);

                            $this->em->persist($entities[$searchKey]);
                        }
                        $entities[$searchKey]->incriminateLongPeriod($search_count);

                        if ($this->isFromPeriod($searchYear, $searchWeek, 4)) {
                            $entities[$searchKey]->incriminateShortPeriod($search_count);
                        }

                        // Make it stick for every 5000 rows.
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
            }

            // Make it stick.
            $this->em->flush();
            $this->em->getConnection()->commit();
            $this->em->clear();
        }

        $reader->close();
    }

    /**
     * Write that parsed data into CSV output file ordered by most searches.
     *
     * @param int $rows
     *   Number of rows to output
     * @param string $filename
     *   The filename to write to in the public folder
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function writeFile(int $rows = 5000, string $filename = 'searchdata.csv'): void
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile($this->destinationDirectory.'/'.$filename);

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
            $rowFromValues = WriterEntityFactory::createRowFromArray($values);
            $writer->addRow($rowFromValues);
        }

        $writer->close();
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
        // @TODO: Change this to use timestamps.
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
