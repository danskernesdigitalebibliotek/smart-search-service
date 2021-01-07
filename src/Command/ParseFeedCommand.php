<?php

namespace App\Command;

use App\Service\FileDownloaderService;
use App\Service\ParseSearchFeedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParseFeedCommand
 */
class ParseFeedCommand extends Command
{
    private string $source;
    private FileDownloaderService $fileDownloader;
    private ParseSearchFeedService $parseSearchFeedService;

    protected static $defaultName = 'app:parse:feed';

    /**
     * ParseFeedCommand constructor.
     *
     * @param string $bindSourceSearchFeed
     * @param FileDownloaderService $fileDownloader
     * @param ParseSearchFeedService $parseSearchFeedService
     */
    public function __construct(string $bindSourceSearchFeed, FileDownloaderService $fileDownloader, ParseSearchFeedService $parseSearchFeedService)
    {
        $this->source = $bindSourceSearchFeed;
        $this->fileDownloader = $fileDownloader;
        $this->parseSearchFeedService = $parseSearchFeedService;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Parse search feed and write CVS file');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('%memory:6s% [%bar%] %elapsed:6s% => %message%');
        $progressBar->setMessage('Starting the download process...');
        $progressBar->start();

        $filename = $this->fileDownloader->download($this->source);

        foreach ($this->parseSearchFeedService->parse($filename) as $count) {
            $progressBar->setMessage('processed: ' . $count);
            $progressBar->advance();
        }

        $progressBar->setMessage('Writing output file...');
        $this->parseSearchFeedService->writeFile();
        $progressBar->finish();

        $this->fileDownloader->cleanUp($this->source);

        return Command::SUCCESS;
    }
}
