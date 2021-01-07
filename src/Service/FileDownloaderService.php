<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileDownloader.
 */
class FileDownloaderService
{
    private string $base;
    private Client $client;
    private static array $filenames = [];
    private Filesystem $filesystem;

    /**
     * FileDownloader constructor.
     *
     * @param string $bindSourceBase
     */
    public function __construct(string $bindSourceBase)
    {
        $this->base = $bindSourceBase;
        $this->client = new Client(['base_uri' => $this->base, 'stream' => true, 'debug' => false]);
        $this->filesystem = new Filesystem();
    }

    /**
     * Download file to temporary storage.
     *
     * Using streams to download the file to keep memory usage as low as possible.
     *
     * @param string $uri
     *   The URI of the file to download
     *
     * @return string
     *   The file the data was downloaded to
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(string $uri): string
    {
        $filename = $this->filesystem->tempnam('/tmp', 'downloaded_');

        $dest = fopen($filename, 'w');
        $source = $this->client->request('GET', $uri);
        $input = $source->getBody()->detach();

        if (!is_null($input)) {
            stream_copy_to_stream($input, $dest);
        }
        else {
            throw new \Exception('Input stream do not exists');
        }

        $source->getBody()->close();
        fclose($dest);

        $this->saveFileName($uri, $filename);

        return $filename;
    }

    /**
     * Remove the temporary file.
     *
     * @param string $uri
     *   The URI that was used to create the temporary file
     *
     * @return bool
     *   true if the clean up is successful else false
     */
    public function cleanUp(string $uri): bool
    {
        try {
            $filename = $this->getFileName($uri);
            $this->filesystem->remove($filename);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Save filename indexed by URI (book keeping helper).
     *
     * @param string $uri
     *   The URI that was used to create the temporary file
     * @param string $filename
     *   The temporary file
     */
    private function saveFileName(string $uri, string $filename): void
    {
        FileDownloaderService::$filenames[$uri] = $filename;
    }

    /**
     * Get filename based on uri (book keeping helper).
     *
     * @param string $uri
     *   The URI that was used to create the temporary file
     *
     * @return string
     *   The temporary file
     *
     * @throws \Exception
     */
    private function getFileName(string $uri): string
    {
        if (!array_key_exists($uri, FileDownloaderService::$filenames)) {
            throw new \Exception('Not found');
        }

        return FileDownloaderService::$filenames[$uri];
    }
}
