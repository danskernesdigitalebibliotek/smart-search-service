<?php

namespace App\Command;

use App\Service\ParseSearchFeedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFeedCommand extends Command
{
    private $ps;

    protected static $defaultName = 'app:parse:feed';

    public function __construct(ParseSearchFeedService $ps)
    {
        $this->ps = $ps;

        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure()
    {
        $this->setDescription('TEST TEST');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('%memory:6s% [%bar%] %elapsed:6s% => %message%');
        $progressBar->start();

        foreach ($this->ps->parse('/app/var/search_feed_2020.csv') as $count) {
            $progressBar->setMessage('Processed: ' . $count);
            $progressBar->advance();
        }
        $this->ps->writeFile();

        return Command::SUCCESS;
    }
}
