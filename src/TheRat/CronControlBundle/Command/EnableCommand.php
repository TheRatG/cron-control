<?php
namespace TheRat\CronControlBundle\Command;

class EnableCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('enable')
            ->setDescription('Rename crontab files for enable');
    }
}
