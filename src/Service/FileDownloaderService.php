<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class FileDownloader.
 */
class FileDownloaderService
{
    private static array $filenames = [];
    private readonly Filesystem $filesystem;

    /**
     * FileDownloader constructor.
     *
     * @param string $sourceBase
     *   The base source URL
     * @param httpClientInterface $client
     *   The HTTP client used to download the file
     */
    public function __construct(
        private readonly string $sourceBase,
        private readonly HttpClientInterface $client
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Download file to temporary storage.
     *
     * Using streams to download the file to keep memory usage as low as possible.
     *
     * @param string $uri
     *   The URI of the file to download
     * @param string $filename
     *   The file to save the downloaded data too
     *
     * @throws TransportExceptionInterface
     */
    public function download(string $uri, string $filename): void
    {
        $dest = fopen($filename, 'w');
        $response = $this->client->request(
            'GET',
            $this->sourceBase.$uri,
            ['timeout' => 5]
        );

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Ressource return non 200 code', $response->getStatusCode());
        }

        stream_copy_to_stream(StreamWrapper::createResource($response, $this->client), $dest);
        fclose($dest);

        $this->saveFileName($uri, $filename);
    }

    /**
     * Remove the temporary file.
     *
     * @param string $uri
     *   The URI that was used to create the temporary file
     *
     *   true if the cleanup is successful else false
     */
    public function cleanUp(string $uri): bool
    {
        try {
            $filename = $this->getFileName($uri);
            $this->filesystem->remove($filename);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Save filename indexed by URI (bookkeeping helper).
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
     * Get filename based on uri (bookkeeping helper).
     *
     * @param string $uri
     *   The URI that was used to create the temporary file
     *
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
