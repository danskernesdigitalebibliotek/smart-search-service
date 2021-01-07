<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileDownloader
 */
class FileDownloaderService {

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
     *
     *
     * @param string $uri
     *
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(string $uri): string
    {
        $filename = $this->filesystem->tempnam('/tmp', 'downloaded_');

        $dest = fopen($filename, 'w');
        $source = $this->client->request('GET', $uri);

        stream_copy_to_stream($source->getBody()->detach(), $dest);

        $source->getBody()->close();
        fclose($dest);

        $this->saveFileName($uri, $filename);

        return $filename;
    }

    /**
     *
     *
     * @param string $uri
     *
     * @return bool
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
     *
     *
     * @param string $uri
     * @param string $filename
     */
    private function saveFileName(string $uri, string $filename)
    {
        FileDownloaderService::$filenames[$uri] = $filename;
    }

    /**
     *
     *
     * @param string $uri
     *
     * @return string
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
