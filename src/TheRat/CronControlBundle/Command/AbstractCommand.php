<?php
namespace TheRat\CronControlBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractCommand extends Command
{
    use ContainerAwareTrait;

    public function getContainer()
    {
        return $this->container;
    }
}
