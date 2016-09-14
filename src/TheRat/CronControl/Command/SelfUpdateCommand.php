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
    const MANIFEST_URI = 'https://raw.githubusercontent.com/TheRatG/cron-control/gh-pages/manifest.json';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates application to the latest version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Looking for updates...');

        $manager = new Manager(Manifest::loadFile(self::MANIFEST_URI));
        $res = $manager->update($this->getApplication()->getVersion(), true, true);
        if ($res) {
            $output->writeln('<info>Update successful!</info>');
        } else {
            $output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}
