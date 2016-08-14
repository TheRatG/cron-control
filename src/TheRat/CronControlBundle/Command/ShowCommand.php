<?php
namespace TheRat\CronControlBundle\Command;

use Symfony\Component\Console\Input\InputOption;

class ShowCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('show')
            ->setDescription('Show tasks')
            ->addOption('disabled', 'd', InputOption::VALUE_NONE, 'Show disabled');
    }
}
