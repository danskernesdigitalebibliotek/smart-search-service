<?php

namespace App\Command;

use App\Service\ParseSearchFeedService;
use App\Service\ParseUserClickedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseUserClickedCommand extends Command
{
    private $ps;

    protected static $defaultName = 'app:parse:user';

    public function __construct(ParseUserClickedService $ps)
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
        foreach ($this->ps->parse('/app/var/Smartsearch1y.csv') as $count) {
            $output->write('.');
        }
        $this->ps->writeFile();

        return Command::SUCCESS;
    }
}
