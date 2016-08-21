<?php
namespace TheRat\CronControl\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheRat\ConsoleApplication;

/**
 * Class InitCommand
 * @package TheRat\CronControl\Command
 */
class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Create cron-control config file')
            ->addArgument('dst-config-file', InputArgument::OPTIONAL, self::DEFAULT_CONFIG_FILENAME);
        $this->addDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('therat.cron_control.config');
    }
}
