<?php
namespace TheRat\CronControlBundle\Command;

/**
 * Class InitCommand
 * @package TheRat\CronControlBundle\Command
 */
class InitCommand extends AbstractCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Create cron-control config file');
    }
}
