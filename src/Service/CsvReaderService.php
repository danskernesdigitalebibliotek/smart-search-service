<?php

namespace App\Service;

/**
 * Class CsvReaderService
 */
class CsvReaderService
{
    /**
     * Read CSV file one line a the time.
     *
     * @param $filename
     *   The file to read.
     *
     * @return \Iterator
     *   Will yield one line at a time.
     */
    public function read($filename): \Iterator
    {
        $file = fopen($filename, 'r');
        while (!feof($file)) {
            $row = fgetcsv($file);

            foreach ($row as &$item) {
                $item = iconv('ISO-8859-1', 'UTF-8', $item);
            }

            yield $row;
        }
    }
}
