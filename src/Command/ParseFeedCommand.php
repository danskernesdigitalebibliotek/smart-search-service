<?php

namespace App\Command;

use App\Service\FileDownloaderService;
use App\Service\ParseSearchFeedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParseFeedCommand.
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
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Parse search feed and write CVS file')
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'If set use this file instead of downloading data.')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset the parsed data (empty out the database)');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('%memory:6s% [%bar%] %elapsed:6s% => %message%');
        $progressBar->start();

        $filename = $input->getOption('filename');
        if (is_null($filename)) {
            $progressBar->setMessage('Starting the download process (might take some time)...');
            $filename = $this->fileDownloader->download($this->source);
        }

        $reset = $input->getOption('reset');
        if ($reset) {
            $progressBar->setMessage('Resetting database...');
            $this->parseSearchFeedService->reset();
        }

        foreach ($this->parseSearchFeedService->parse($filename) as $counts) {
            $progressBar->setMessage('processed: '.$counts['processed'].' inserted: '.$counts['inserted']);
            $progressBar->advance();
        }

        $progressBar->setMessage('Writing output file...');
        $this->parseSearchFeedService->writeFile();
        $progressBar->finish();

        $this->fileDownloader->cleanUp($this->source);

        return Command::SUCCESS;
    }
}
