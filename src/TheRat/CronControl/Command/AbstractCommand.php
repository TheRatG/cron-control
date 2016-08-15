<?php
namespace TheRat\CronControl\Command;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TheRat\CronControl\Config;

abstract class AbstractCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Logger
     */
    protected $logger;

    public function getLogger()
    {
        return $this->getConfig()->getLogger();
    }

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
