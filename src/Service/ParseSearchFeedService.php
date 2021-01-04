<?php

namespace App\Service;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;


class ParseSearchFeedService {

    private string $source;
    private FileDownloader $fileDownloader;

    public function __construct(string $bindSourceSearchFeed, FileDownloader $fileDownloader)
    {
        $this->source = $bindSourceSearchFeed;
        $this->fileDownloader = $fileDownloader;
    }

    public function test() {
        $filename = $this->fileDownloader->download($this->source);

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filename);

        foreach ($reader->getSheetIterator() as $sheet) {
            /* @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $search_year = (int) $row->getCellAtIndex(0)->getValue();
                $search_week = (int) $row->getCellAtIndex(1)->getValue();


                if (ting_smart_search_is_from_period($search_year, $search_week)) {

                    $search_key = $row->getCellAtIndex(2)->getValue();
                    $search_count = (int) $row->getCellAtIndex(3)->getValue();


//                    if (array_key_exists($search_key, $search_data) && is_numeric($number_of_searches)) {
//                        $search_data[$search_key]['long_period'] += $number_of_searches;
//                    }
//                    else {
//                        $search_data[$search_key] = array('long_period' => $number_of_searches, 'short_period' => 0);
//                    }
//                    if (ting_smart_search_is_from_period($line, 4)) {
//                        $search_data[$search_key]['short_period'] += $number_of_searches;
//                    }

                }
            }
        }

        $reader->close();
        $this->fileDownloader->cleanUp($this->source);
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


