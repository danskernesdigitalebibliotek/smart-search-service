<?php

namespace App\Command;

use App\Service\FileDownloaderService;
use App\Service\ParseUserClickedService;
use Box\Spout\Common\Exception\SpoutException;
use Doctrine\DBAL\Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParseUserClickedCommand.
 */
class ParseUserClickedCommand extends Command
{
    private string $source;
    private FileDownloaderService $fileDownloader;
    private ParseUserClickedService $parseUserClickedService;
    private LoggerInterface $logger;

    protected static $defaultName = 'app:parse:user';

    /**
     * ParseUserClickedCommand constructor.
     *
     * @param string $bindAutoDataSource
     * @param FileDownloaderService $fileDownloader
     * @param ParseUserClickedService $parseUserClickedService
     */
    public function __construct(string $bindAutoDataSource, FileDownloaderService $fileDownloader, ParseUserClickedService $parseUserClickedService, LoggerInterface $informationLogger)
    {
        $this->source = $bindAutoDataSource;
        $this->fileDownloader = $fileDownloader;
        $this->parseUserClickedService = $parseUserClickedService;
        $this->logger = $informationLogger;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Parse user clicked information feed and write serialized txt file')
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
            $this->logger->info('Starting download of file ('.$this->source.')');
            $progressBar->setMessage('Starting the download process (might take some time)...');

            try {
                $filename = $this->fileDownloader->download($this->source);
            } catch (GuzzleException $e) {
                $this->logger->info('Download failed of file ('.$this->source.') : '.$e->getMessage());
                return Command::FAILURE;
            }
        }

        $reset = $input->getOption('reset');
        if ($reset) {
            $this->logger->info('Resetting database');
            $progressBar->setMessage('Resetting database...');
            try {
                $this->parseUserClickedService->reset();
            } catch (Exception $e) {
                $this->logger->error('Resetting database failed : '.$e->getMessage());
                return Command::FAILURE;
            }
        }

        try {
            foreach ($this->parseUserClickedService->parse($filename) as $counts) {
                $progressBar->setMessage('processed: '.$counts['processed'].' inserted: '.$counts['inserted']);
                $progressBar->advance();
            }
        } catch (SpoutException $e) {
            $this->logger->error('Error reading CSV file : '.$e->getMessage());
            return Command::FAILURE;
        }

        $this->logger->info('Writing output file');
        $progressBar->setMessage('Writing output file...');
        try {
            $this->parseUserClickedService->writeFile();
        } catch (\Exception $e) {
            $this->logger->error('Error writing serialized text file : '.$e->getMessage());
            return Command::FAILURE;
        }
        $progressBar->finish();

        $this->fileDownloader->cleanUp($this->source);

        $this->logger->info('Completed');

        return Command::SUCCESS;
    }
}
