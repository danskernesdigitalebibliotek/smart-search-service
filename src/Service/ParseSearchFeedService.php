<?php

namespace App\Service;

use App\Entity\SearchFeed;
use App\Repository\SearchFeedRepository;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ParseSearchFeedService
 */
class ParseSearchFeedService {

    private string $projectDir;
    private SearchFeedRepository $searchFeedRepos;
    private EntityManagerInterface $em;

    /**
     * ParseSearchFeedService constructor.
     *
     * @param string $bindProjectDir
     * @param EntityManagerInterface $entityManager
     * @param SearchFeedRepository $searchFeedRepository
     */
    public function __construct(string $bindProjectDir, EntityManagerInterface $entityManager, SearchFeedRepository $searchFeedRepository)
    {
        $this->projectDir = $bindProjectDir;
        $this->em = $entityManager;
        $this->searchFeedRepos = $searchFeedRepository;
    }

    /**
     * Parse CSV file with search query information.
     *
     * Note the function yield for every 500 rows parsed to provide feedback on the parsing process.
     *
     * @param string $filename
     *   If provided the file will be used as input else file will be downloaded.
     *
     * @return \Generator
     *   Yield for every 500 rows.
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function parse(string $filename): \Generator
    {
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filename);

        $rowsCount = 0;

        /* @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            /* @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $searchYear = (int) $row->getCellAtIndex(0)->getValue();
                $searchWeek = (int) $row->getCellAtIndex(1)->getValue();

                $rowsCount++;

                // Yield progress.
                if ($rowsCount % 500 == 0) {
                    yield $rowsCount;
                }

                if ($this->isFromPeriod($searchYear, $searchWeek)) {
                    $searchKey = $row->getCellAtIndex(2)->getValue();
                    $search_count = (int) $row->getCellAtIndex(3)->getValue();

                    // Trying to fix string encoding.
                    $searchKey = mb_convert_encoding($searchKey, 'UTF-8', mb_detect_encoding($searchKey));

                    // We exclude complex search strings.
                    if ($this->isValid($searchKey)) {
                        $entity = $this->searchFeedRepos->findOneBy(['search' => $searchKey]);
                        if (is_null($entity)) {
                            $entity = new SearchFeed();
                            $entity->setYear($searchYear);
                            $entity->setWeek($searchWeek);
                            $entity->setSearch($searchKey);
                            $this->em->persist($entity);
                        }
                        $entity->incriminateLongPeriod($search_count);

                        if ($this->isFromPeriod($searchYear, $searchWeek, 4)) {
                            $entity->incriminateShortPeriod($search_count);
                        }

                        // Make it stick for every 500 rows.
                        if (0 === $rowsCount % 500) {
                            $this->em->flush();
                            $this->em->clear();
                        }
                    }
                }
            }

            // Make it stick.
            $this->em->flush();
        }

        $reader->close();
    }

    /**
     * Write that parsed data into CSV output file ordered by most searches.
     *
     * @param int $rows
     *   Number of rows to output.
     * @param string $filename
     *   The filename to write to in the public folder.
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function writeFile(int $rows = 5000, string $filename = 'searchdata.csv')
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile($this->projectDir . '/public/' . $filename);

        $query = $this->em->createQueryBuilder()
            ->select('s')
            ->from(SearchFeed::class , 's')
            ->orderBy('s.longPeriod', 'DESC')
            ->setMaxResults($rows)
            ->getQuery();

        $iterable = $query->toIterable();
        foreach ($iterable as $entity) {
            $values = [$entity->getSearch(), $entity->getLongPeriod(), $entity->getShortPeriod()];
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
    public function reset() {
        $this->searchFeedRepos->truncateTable();
    }

    /**
     * Check if smart search record is within look-back.
     *
     * @param int $year
     *   Year number.
     * @param int $week
     *   Week number.
     * @param int $period
     *   Weeks from now that the year and week should be with in.
     *
     * @return bool
     */
    private function isFromPeriod(int $year, int $week, int $period = 52): bool
    {
        // @TODO: Change this to use timestamps.
        $date = new \DateTime();
        $nowYear = (int) $date->format("Y");
        $nowWeek = (int) $date->format("W");
        if ($year == $nowYear && $nowWeek - $period <= $week) {
            return true;
        }
        elseif (($year == $nowYear - 1) && $nowWeek <= $period) {
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
     *   The search key.
     *
     * @return bool
     *   The result of the validation.
     */
    private function isValid(string $key): bool
    {
        if (strpos($key, '=') !== false || strpos($key, '(') !== false || strpos($key, '*') !== false ) {
            return false;
        }

        $val = str_replace(',', '', $key);
        if (is_numeric($val)) {
            return false;
        }

        return true;
    }
}

