<?php
namespace TheRat;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsoleApplication
 * @package TheRat
 */
class ConsoleApplication extends Application
{
    const INPUT_OPTION_CONFIG = 'config';

    /**
     * @var ConsoleKernel
     */
    protected $kernel;

    /**
     * ConsoleApplication constructor.
     * @param string        $name
     * @param ConsoleKernel $kernel
     */
    public function __construct($name, ConsoleKernel $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct($name, '@package_version@');
    }

    /**
     * @return ConsoleKernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->getKernel()->boot();
        $commands = $this->getKernel()->getCommands();
        if ($commands) {
            $this->addCommands($commands);
        }

        return parent::doRun($input, $output);
    }

    public function getCustomConfigFilename()
    {
        return sprintf('~/.%s.yml', $this->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(
            new InputOption(
                self::INPUT_OPTION_CONFIG,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Config filename',
                $this->getCustomConfigFilename()
            )
        );

        return $definition;
    }
}
