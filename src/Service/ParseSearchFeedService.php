<?php

namespace App\Service;

use App\Entity\SearchFeed;
use App\Repository\SearchFeedRepository;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Doctrine\ORM\EntityManagerInterface;


class ParseSearchFeedService {

    private string $projectDir;
    private string $source;
    private FileDownloader $fileDownloader;
    private SearchFeedRepository $searchFeedRepos;
    private EntityManagerInterface $em;

    public function __construct(string $bindSourceSearchFeed, string $bindProjectDir, EntityManagerInterface $entityManager, SearchFeedRepository $searchFeedRepository, FileDownloader $fileDownloader)
    {
        $this->source = $bindSourceSearchFeed;
        $this->projectDir = $bindProjectDir;
        $this->em = $entityManager;
        $this->searchFeedRepos = $searchFeedRepository;
        $this->fileDownloader = $fileDownloader;
    }

    public function parse(string $filename = '') {
        if ($filename === '') {
            $filename = $this->fileDownloader->download($this->source);
        }

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filename);

        $rowsCount = 0;

        /* @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            /* @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $search_year = (int) $row->getCellAtIndex(0)->getValue();
                $search_week = (int) $row->getCellAtIndex(1)->getValue();

                // Debug code.
                $rowsCount++;
                if ($rowsCount % 1000 == 0) yield $rowsCount;

                if ($this->isFromPeriod($search_year, $search_week)) {
                    $search_key = $row->getCellAtIndex(2)->getValue();
                    $search_count = (int) $row->getCellAtIndex(3)->getValue();

                    // We exclude complex search strings. These are most often made by
                    // professionals and don't need to be addresses by the module.
                    if (!(strpos($search_key, '=') !== false || strpos($search_key, '(') !== false)) {
                        $entity = $this->searchFeedRepos->findOneBy(['search' => $search_key]);
                        if (is_null($entity)) {
                            $entity = new SearchFeed();
                            $entity->setYear($search_year);
                            $entity->setWeek($search_week);
                            $entity->setSearch($search_key);
                            $this->em->persist($entity);
                        }
                        $entity->incriminateLongPeriod($search_count);

                        if ($this->isFromPeriod($search_year, $search_week, 4)) {
                            $entity->incriminateShortPeriod($search_count);
                        }

                        // Make it stick.
                        $this->em->flush();
                    }
                }
            }
        }

        $reader->close();

        //$this->fileDownloader->cleanUp($this->source);
    }

    public function writeFile($rows = 5000, $filename = 'searchdata.csv')
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile($this->projectDir . '/public/' . $filename);

        $query = $this->em->createQuery('SELECT s FROM SearchFeed s ORDER BY longPeriod DESC LIMIT ' . $rows);
        $iterable = $query->iterate();
        while (($row = $iterable->next()) !== false) {
            $values = ['Carl', 'is', 'great!'];
            $rowFromValues = WriterEntityFactory::createRowFromArray($values);
            $writer->addRow($rowFromValues);
        }

        //$line = array($search_key, $data['long_period'], $data['short_period']);

        $writer->close();
    }

    public function reset() {
        // @TODO: Move into repos class.
        $connection = $this->em->getConnection();
        $sql = $connection->getDatabasePlatform()->getTruncateTableSQL('SearchFeed');
        $connection->executeStatement($sql);
    }

    /**
     * Check if smart search record is within look-back.
     *
     * @param int $year
     * @param int $week
     * @param int $lookback
     *
     * @return bool
     */
    public function isFromPeriod(int $year, int $week, int $lookback = 52): bool
    {
        $date = new \DateTime();
        $nowYear = (int) $date->format("Y");
        $nowWeek = (int) $date->format("W");
        if ($year == $nowYear && $nowWeek - $lookback <= $week) {
            return true;
        }
        elseif (($year == $nowYear - 1) && $nowWeek <= $lookback) {
            if ($week >= (52 - $lookback + $nowWeek)) {
                return true;
            }
        }

        return false;
    }
}


