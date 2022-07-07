<?php

namespace App\Command;

use App\Service\FileDownloaderService;
use App\Service\ParseSearchFeedService;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:parse:feed',
)]
class ParseFeedCommand extends Command
{
    protected static $defaultName = 'app:parse:feed';
    private readonly Filesystem $filesystem;

    /**
     * ParseFeedCommand constructor.
     *
     * @param string $sourceSearchFeed
     * @param FileDownloaderService $fileDownloader
     * @param ParseSearchFeedService $parseSearchFeedService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly string $sourceSearchFeed,
        private readonly FileDownloaderService $fileDownloader,
        private readonly ParseSearchFeedService $parseSearchFeedService,
        private readonly LoggerInterface $logger
    ) {
        $this->filesystem = new Filesystem();

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
            $this->logger->info('Starting download of file ('.$this->sourceSearchFeed.')');
            $progressBar->setMessage('Starting the download process (might take some time)...');
            $progressBar->display();
            try {
                $filename = $this->filesystem->tempnam('/tmp', 'downloaded_');
                $this->fileDownloader->download($this->sourceSearchFeed, $filename);
            } catch (TransportExceptionInterface $e) {
                $this->logger->info('Download failed of file ('.$this->sourceSearchFeed.') : '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        $reset = $input->getOption('reset');
        if ($reset) {
            $this->logger->info('Resetting database');
            $progressBar->setMessage('Resetting database...');
            $progressBar->display();
            try {
                $this->parseSearchFeedService->reset();
            } catch (Exception $e) {
                $this->logger->error('Resetting database failed : '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        try {
            foreach ($this->parseSearchFeedService->parse($filename) as $counts) {
                $progressBar->setMessage('processed: '.$counts['processed'].' inserted: '.$counts['inserted'].' updated: '.$counts['updated']);
                $progressBar->advance();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error reading CSV file : '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->logger->info('Writing output file');
        $progressBar->setMessage('Writing output file...');
        $progressBar->display();
        try {
            $this->parseSearchFeedService->writeFile();
        } catch (\Exception $e) {
            $this->logger->error('Error writing CSV file : '.$e->getMessage());

            return Command::FAILURE;
        }

        $progressBar->finish();
        $output->writeln('');

        $this->fileDownloader->cleanUp($this->sourceSearchFeed);

        $this->logger->info('Completed');

        return Command::SUCCESS;
    }
}
