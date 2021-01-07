<?php

namespace App\Service;

use App\Entity\UserClickedFeed;
use App\Repository\UserClickedFeedRepository;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ParseUserClickedService
 */
class ParseUserClickedService
{
    private string $source;
    private string $projectDir;
    private EntityManagerInterface $em;
    private UserClickedFeedRepository $userClickedRepos;
    private FileDownloader $fileDownloader;

    /**
     * ParseUserClickedService constructor.
     *
     * @param string $bindAutoDataSource
     * @param string $bindProjectDir
     * @param EntityManagerInterface $entityManager
     * @param UserClickedFeedRepository $UserClickedFeedRepository
     * @param FileDownloader $fileDownloader
     */
    public function __construct(string $bindAutoDataSource, string $bindProjectDir, EntityManagerInterface $entityManager, UserClickedFeedRepository $UserClickedFeedRepository, FileDownloader $fileDownloader)
    {
        $this->source = $bindAutoDataSource;
        $this->projectDir = $bindProjectDir;
        $this->em = $entityManager;
        $this->userClickedRepos = $UserClickedFeedRepository;
        $this->fileDownloader = $fileDownloader;
    }

    /**
     * Parse CSV file with user clicked information.
     *
     * Note the function yield for every 1000 rows parsed to provide feedback on the parsing process.
     *
     * @param string $filename
     *   If provided the file will be used as input else file will be downloaded.
     *
     * @return \Generator
     *   Yield for every 1000 rows.
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parse(string $filename = '')
    {
        if ($filename === '') {
            $filename = $this->fileDownloader->download($this->source);
        }

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->setFieldDelimiter(';');
        $reader->open($filename);

        $rowsCount = 0;

        /* @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            /* @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $rowsCount++;

                // Skip first row which is headers.
                if ($rowsCount === 1) {
                    continue;
                }
                $page = $row->getCellAtIndex(1)->getValue();

                // Debug code (yield progress).
                if ($rowsCount % 500 == 0) yield $rowsCount;

                // Find the linked data-well post id (PID).
                $pid = $this->getPidFromPage($page);
                if (!empty($pid)) {
                    $search = $row->getCellAtIndex(0)->getValue();
                    $clicks = (int) $row->getCellAtIndex(2)->getValue();

                    $entity = $this->userClickedRepos->findOneBy([
                        'search' => $search,
                        'pid' => $pid,
                    ]);
                    if (is_null($entity)) {
                        $entity = new UserClickedFeed();
                        $entity->setPid($pid);
                        $entity->setSearch($search);
                        $this->em->persist($entity);
                    }
                    $entity->incriminateClicks($clicks);

                    // Make it stick for every 500 rows.
                    if (0 === $rowsCount % 500) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }
            }

            // Make it stick.
            $this->em->flush();
        }

        $reader->close();
        $this->fileDownloader->cleanUp($this->source);
    }

    /**
     * Write auto data with serialized object.
     *
     * @param string $filename
     *   The file name to store the serialized data object in public folder.
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function writeFile(string $filename = 'autodata.txt')
    {
        // This is done with raw SQL statements as the query build will not accept the sub-query.
        $subQuery = '(SELECT pid, search, sum(clicks) AS clicks FROM user_clicked_feed u GROUP BY search, pid)';
        $query = 'SELECT * FROM ' . $subQuery . ' WHERE clicks > 2 ORDER BY search ASC, clicks DESC';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $iterable =  $stmt->iterateAssociative();
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
        file_put_contents($this->projectDir . '/public/' . $filename, $data);
    }

    /**
     * Reset the database table (truncate it).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function reset() {
        $this->userClickedRepos->truncateTable();
    }

    /**
     * Try finding PID in the page clicked information.
     *
     * Also filter out eReolen links (why, don't know).
     *
     * @param string $page
     *   Page part of the data from the source file.
     *
     * @return string
     *   The pid if found else the empty string.
     */
    private function getPidFromPage(string $page): string
    {
        if (!(strpos($page, 'ereolen') !== false)) {
            preg_match("%ting\.(collection|object)\.(.+)%", $page, $matches);
            if ($matches && isset($matches[2])) {
                return $matches[2];
            }
        }

        return '';
    }
}
