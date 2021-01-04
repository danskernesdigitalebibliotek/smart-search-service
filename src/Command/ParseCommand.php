<?php

namespace App\Command;

use App\Service\ParseSearchFeedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends Command
{
    private $ps;

    protected static $defaultName = 'app:parse';

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
        $this->ps->test();

        return Command::SUCCESS;
    }
}
