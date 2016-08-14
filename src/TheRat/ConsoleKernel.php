<?php
namespace TheRat;

use Composer\Util\Filesystem;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

/**
 * Class ConsoleKernel
 * @package TheRat
 */
abstract class ConsoleKernel
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var bool
     */
    protected $booted;

    /**
     * @var string
     */
    protected $customConfigFilename;

    public function __construct()
    {
        if (!class_exists('Symfony\Component\Finder\Finder')) {
            throw new \RuntimeException('You need the symfony/finder component to register bundle commands.');
        }

        $this->fileSystem = new Filesystem();
    }

    /**
     * @return boolean
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @return string[]
     */
    abstract public function registerCommandDirectories();


    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * @return Extension[]
     */
    public function registerExtension()
    {
        return [];
    }

    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        $this->initializeContainer();

        $this->booted = true;
    }

    /**
     * @return Filesystem
     */
    public function getFileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        $commands = [];
        foreach ($this->registerCommandDirectories() as $dir => $namespace) {
            $commands = array_merge($this->findCommands($dir, $namespace), $commands);
        }

        return $commands;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = dirname($r->getFileName());
        }

        return $this->rootDir;
    }

    public function getRootDir()
    {
        return dirname($this->getAppDir());
    }

    protected function findCommands($dir, $namespace)
    {
        if (!is_dir($dir)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($dir);

        $prefix = $namespace.'\\Command';
        $result = [];
        foreach ($finder as $file) {
            require_once $file;

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\'.str_replace('/', '\\', $relativePath);
            }
            $class = $ns.'\\'.$file->getBasename('.php');

            $r = new \ReflectionClass($class);
            if (!$r->isAbstract()
                && !$r->getConstructor()->getNumberOfRequiredParameters()
            ) {
                /** @var Command $command */
                $command = $r->newInstance();
                if ($command instanceof ContainerAwareInterface) {
                    $command->setContainer($this->getContainer());
                }
                $result[] = $command;
            }
        }

        return $result;
    }

    /**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer()
    {
        $container = $this->buildContainer();
        $container->compile();
    }

    protected function buildContainer()
    {
        $containerBuilder = new ContainerBuilder();

        foreach ($this->registerExtension() as $extension) {
            $containerBuilder->registerExtension($extension);
        }

        $customConfigFilename = $this->getCustomConfigFilename();
        $paths = [$this->getRootDir()];
        if ($customConfigFilename) {
            $paths[] = dirname($customConfigFilename);
        }

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($paths));
        $this->registerContainerConfiguration($loader);

        if ($customConfigFilename) {
            $loader->load(basename($customConfigFilename));
        }

        return $containerBuilder;
    }

    protected function getCustomConfigFilename()
    {
        if (!$this->isBooted()) {
            $input = new ArgvInput();
            $customConfigFilename = $input->getParameterOption(['--config', 't']);
            if ($customConfigFilename) {
                if (!$this->getFileSystem()->isAbsolutePath($customConfigFilename)) {
                    $customConfigFilename = getcwd().DIRECTORY_SEPARATOR.$customConfigFilename;
                }

                if (file_exists($customConfigFilename)) {
                    $this->customConfigFilename = $customConfigFilename;
                }
            }
        }

        return $this->customConfigFilename;
    }
}
