<?php
namespace TheRat;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class GeggsApplication
 */
class CronControlApplication extends Application
{
    const APP_NAME = 'cron-control';

    const APP_CONFIG_FILE = 'cron-control.yml';

    /**
     * @var string
     */
    protected $configDefaultPath;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Set up application:
     */
    public function __construct()
    {
        parent::__construct(self::APP_NAME, '@package_version@');

        $this->registerCommands($this->getBundleDir().DIRECTORY_SEPARATOR.'Command', $this->getNamespace());
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return dirname(__DIR__);
    }

    /**
     * @return string
     */
    public function getBundleDir()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                $this->getRootDir(),
                'src',
                'TheRat',
                'CronControlBundle',
            ]
        );
    }

    /**
     * @return string
     */
    public function getConfigDefaultPath()
    {
        if (empty($this->configDefaultPath) || !file_exists($this->configDefaultPath)) {
            $this->configDefaultPath =
                getcwd().DIRECTORY_SEPARATOR.'.geggs'.DIRECTORY_SEPARATOR.self::APP_CONFIG_FILE;

            if (!file_exists($this->configDefaultPath)) {
                $this->configDefaultPath = dirname(__DIR__).DIRECTORY_SEPARATOR.self::APP_CONFIG_FILE;
            }
        }

        return $this->configDefaultPath;
    }

    /**
     * @return ContainerBuilder
     * http://symfony.com/doc/current/components/dependency_injection/compilation.html
     */
    public function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        // Load cli options:
        $input = new ArgvInput();
        $configPath = $input->getParameterOption(['--config', '-c'], $this->getConfigDefaultPath());

        // Make sure to set the full path when it is declared relative
        // This will fix some issues in windows.
        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($configPath)) {
            $configPath = getcwd().DIRECTORY_SEPARATOR.$configPath;
        }

        $this->container = new ContainerBuilder();
        $extension = new TheRatGeggsExtension();
        $this->container->registerExtension($extension);

        $loader = new YamlFileLoader($this->container, new FileLocator(dirname($configPath)));
        $loader->load(basename($configPath));

        $this->container->compile();

        return $this->container;
    }

    protected function registerCommands($dir, $namespace)
    {
        if (!is_dir($dir)) {
            return;
        }

        if (!class_exists('Symfony\Component\Finder\Finder')) {
            throw new \RuntimeException('You need the symfony/finder component to register bundle commands.');
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($dir);

        $prefix = $namespace.'\\Command';
        foreach ($finder as $file) {
            require_once $file;

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\'.str_replace('/', '\\', $relativePath);
            }
            $class = $ns.'\\'.$file->getBasename('.php');
            if ($this->container) {
                $alias = 'console.command.'.strtolower(str_replace('\\', '_', $class));
                if ($this->container->has($alias)) {
                    continue;
                }
            }
            $r = new \ReflectionClass($class);
            if ($r->isSubclassOf('TheRat\\CronControlBundle\\Command\\AbstractCommand')
                && !$r->isAbstract()
                && !$r->getConstructor()->getNumberOfRequiredParameters()
            ) {
                /** @var \TheRat\CronControlBundle\Command\AbstractCommand $command */
                $command = $r->newInstance();
                $command->setContainer($this->getContainer());

                $this->add($command);
            }
        }

        $this->add(new SelfUpdateCommand());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to config',
                $this->getConfigDefaultPath()
            )
        );
        $definition->addOption(
            new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Try operation but make no changes')
        );

        return $definition;
    }

    private function getNamespace()
    {
        return 'TheRat\\CronControlBundle';
    }
}
