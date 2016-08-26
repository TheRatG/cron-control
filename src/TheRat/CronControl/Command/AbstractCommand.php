<?php
namespace TheRat\CronControl\Command;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TheRat\CronControl\Config;

abstract class AbstractCommand extends Command implements ContainerAwareInterface
{
    const INPUT_OPTION_CONFIG = 'config';
    const DEFAULT_CONFIG_FILENAME = '~/.cron-control.yml';

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

    protected function addDryRunOption()
    {
        $this->addOption('dry-run', 't', InputOption::VALUE_NONE, 'Try operation but make no changes');

        return $this;
    }

    protected function checkCustomConfigFile(InputInterface $input, OutputInterface $output)
    {
        $customConfigFilename = $input->getOption('config');
        if (!file_exists($customConfigFilename)) {
            throw new \RuntimeException(
                sprintf(
                    'Config file not found "%s", you could use "init" command for creating config file.',
                    $customConfigFilename
                )
            );
        }
    }
}
