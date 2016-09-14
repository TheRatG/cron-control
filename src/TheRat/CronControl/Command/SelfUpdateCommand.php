<?php
namespace TheRat\CronControl\Command;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SelfUpdateCommand
 * @package TheRat\CronControl\Command
 */
class SelfUpdateCommand extends AbstractCommand
{
    const MANIFEST_URI = 'https://raw.githubusercontent.com/TheRatG/cron-control/gh-pages/manifest.json';

    /**
     * The manifest file URI.
     *
     * @var string
     */
    private $manifestUri;

    /**
     * SelfUpdateCommand constructor.
     * @param null|string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setManifestUri(self::MANIFEST_URI);
    }

    /**
     * Sets the manifest URI.
     *
     * @param string $uri The URI.
     */
    public function setManifestUri($uri)
    {
        $this->manifestUri = $uri;
    }

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates cron-control.phar to the latest version')
            ->addOption(
                'pre',
                'p',
                InputOption::VALUE_NONE,
                'Allow pre-release updates.',
                true
            )
            ->addOption(
                'redo',
                'r',
                InputOption::VALUE_NONE,
                'Redownload update if already using current version.'
            )
            ->addOption(
                'upgrade',
                'u',
                InputOption::VALUE_NONE,
                'Upgrade to next major release, if available.',
                true
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_null($this->manifestUri)) {
            throw new \LogicException(
                'No manifest URI has been configured.'
            );
        }

        $output->writeln('Looking for updates...');

        $manager = new Manager(Manifest::loadFile(self::MANIFEST_URI));
        $res = $manager->update(
            $this->getApplication()->getVersion(),
            $input->getOption('upgrade'),
            $input->getOption('pre')
        );
        if ($res) {
            $output->writeln('<info>Update successful!</info>');
        } else {
            $output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}
