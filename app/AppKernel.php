<?php
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use TheRat\ConsoleKernel;
use TheRat\CronControl\DependencyInjection\TheRatCronControlExtension;

/**
 * Class AppKernel
 */
class AppKernel extends ConsoleKernel
{
    /**
     * @inheritdoc
     */
    public function registerCommandDirectories()
    {
        return [
            implode(
                DIRECTORY_SEPARATOR,
                [
                    'src',
                    'TheRat',
                    'CronControl',
                    'Command'
                ]
            ) => 'TheRat\\CronControl',
        ];
    }

    /**
     * @return Extension[]
     */
    public function registerExtension()
    {
        return [
            new TheRatCronControlExtension(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getAppDir().'/config/config.yml');
    }
}
