<?php
namespace TheRat\CronControl\Command;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SelfUpdateCommand
 * @package TheRat\CronControl\Command
 */
class SelfUpdateCommand extends AbstractCommand
{
    const MANIFEST_FILE = 'https://raw.githubusercontent.com/TheRatG/cron-control/gh-pages/manifest.json';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates cron-control.phar to the latest version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        $manager->update($this->getApplication()->getVersion(), true, true);
    }
}
