<?php
namespace TheRat;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
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
    protected $name;

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

    public function __construct($name)
    {
        if (!class_exists('Symfony\Component\Finder\Finder')) {
            throw new \RuntimeException('You need the symfony/finder component to register bundle commands.');
        }

        $this->name = $name;
        $this->fileSystem = new Filesystem();
    }

    public static function homeDir()
    {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        } elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }

        if (empty($home) && function_exists('posix_getpwuid')) {
            $data = posix_getpwuid(posix_getuid());
            $home = $data['dir'];
        }

        return empty($home) ? null : $home;
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

    public function getCustomConfigFilename()
    {
        if (!$this->isBooted()) {
            $input = new ArgvInput();
            $customConfigFilename = $input->getParameterOption(['--config', '-c']);
            if (!file_exists($customConfigFilename)) {
                if ($customConfigFilename && !$this->getFileSystem()->isAbsolutePath($customConfigFilename)) {
                    $customConfigFilename = getcwd().DIRECTORY_SEPARATOR.$customConfigFilename;
                    if (file_exists($customConfigFilename)) {
                        $this->customConfigFilename = $customConfigFilename;
                    }
                }
                if (!$this->customConfigFilename) {
                    $this->customConfigFilename = $this->getHomeConfigFilename();
                }
            } else {
                $this->customConfigFilename = $customConfigFilename;
            }
        }

        return $this->customConfigFilename;
    }

    public function getHomeConfigFilename()
    {
        return sprintf('%s/.%s.yml', self::homeDir(), $this->getName());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    protected function findCommands($dir, $namespace)
    {
        if (!$this->fileSystem->isAbsolutePath($dir)) {
            $dir = $this->getRootDir().DIRECTORY_SEPARATOR.$dir;
        }

        if (!is_dir($dir)) {
            throw new \RuntimeException(sprintf('Command dir "%s" does not exists', $dir));
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

        $this->container = $container;
    }

    protected function buildContainer()
    {
        $containerBuilder = new ContainerBuilder();

        $extensions = [];
        foreach ($this->registerExtension() as $extension) {
            $containerBuilder->registerExtension($extension);
            $extensions[] = $extension->getAlias();
        }

        // ensure these extensions are implicitly loaded
        $containerBuilder->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass($extensions));

        $customConfigFilename = $this->getCustomConfigFilename();
        $existsCustomConfigFilename = file_exists($customConfigFilename);

        $paths = [$this->getRootDir()];
        if ($existsCustomConfigFilename) {
            $paths[] = dirname($customConfigFilename);
        }

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($paths));
        $this->registerContainerConfiguration($loader);

        if ($existsCustomConfigFilename) {
            $loader->load(basename($customConfigFilename));
        }

        $containerBuilder->set('kernel', $this);

        return $containerBuilder;
    }
}
