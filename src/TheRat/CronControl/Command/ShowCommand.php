<?php
namespace TheRat\CronControl\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TheRat\ConsoleKernel;
use TheRat\CronControl\Config;

/**
 * Class ShowCommand
 * @package TheRat\CronControl\Command
 */
class ShowCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('show')
            ->setDescription('Show tasks')
            ->addOption('full', 'f', InputOption::VALUE_NONE, 'Show crontab file content');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        /** @var Config $config */
        $config = $this->getContainer()->get('therat.cron_control.config');

        $symfonyStyle->title('Cron control configuration');

        /** @var ConsoleKernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $customConfigFilename = $kernel->getCustomConfigFilename();

        $symfonyStyle->writeln('Custom config filename: '.($customConfigFilename ?: 'none'));
        $symfonyStyle->newLine();
        $symfonyStyle->table(['Glob patterns'], [$config->getGlobPatterns()]);

        $symfonyStyle->table(['Enabled crontab'], [$config->getEnabledCrontabFiles()]);
        $symfonyStyle->table(['Disabled crontab'], [$config->getDisabledCrontabFiles()]);
    }
}
