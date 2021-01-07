<?php

namespace App\Command;

use App\Service\FileDownloaderService;
use App\Service\ParseSearchFeedService;
use App\Service\ParseUserClickedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParseUserClickedCommand
 */
class ParseUserClickedCommand extends Command
{
    private string $source;
    private FileDownloaderService $fileDownloader;
    private ParseUserClickedService $parseUserClickedService;

    protected static $defaultName = 'app:parse:user';

    /**
     * ParseUserClickedCommand constructor.
     *
     * @param string $bindAutoDataSource
     * @param FileDownloaderService $fileDownloader
     * @param ParseUserClickedService $parseUserClickedService
     */
    public function __construct(string $bindAutoDataSource, FileDownloaderService $fileDownloader, ParseUserClickedService $parseUserClickedService)
    {
        $this->source = $bindAutoDataSource;
        $this->fileDownloader = $fileDownloader;
        $this->parseUserClickedService = $parseUserClickedService;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Parse user clicked information feed and write serialized txt file');
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

        foreach ($this->parseUserClickedService->parse($filename) as $count) {
            $progressBar->setMessage('processed: ' . $count);
            $progressBar->advance();
        }

        $progressBar->setMessage('Writing output file...');
        $this->parseUserClickedService->writeFile();
        $progressBar->finish();

        $this->fileDownloader->cleanUp($this->source);

        return Command::SUCCESS;
    }
}
