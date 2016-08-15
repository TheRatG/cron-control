<?php
namespace TheRat\CronControl\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TheRat\CronControl\Config;

abstract class AbstractCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        /** @var Config $config */
        $config = $this->getContainer()->get('therat.cron_control.config');

        return $config;
    }
}
