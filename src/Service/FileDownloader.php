<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

class FileDownloader {

    private string $base;
    private Client $client;
    private array $filenames;
    private Filesystem $filesystem;

    public function __construct(string $bindSourceBase)
    {
        $this->base = $bindSourceBase;
        $this->client = new Client(['base_uri' => $this->base, 'stream' => true, 'debug' => false]);
        $this->filesystem = new Filesystem();

        $this->filenames = [];
    }

    public function download($uri): string
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

    public function cleanUp($uri): bool
    {
        try {
            $filename = $this->getFileName($uri);
            $this->filesystem->remove($filename);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    private function saveFileName(string $uri, string $filename)
    {
        $this->filenames[$uri] = $filename;
    }

    private function getFileName(string $uri): string
    {
        if (array_key_exists($uri, $this->filenames)) {
            throw new \Exception('Not found');
        }

        return $this->filenames[$uri];
    }
}
