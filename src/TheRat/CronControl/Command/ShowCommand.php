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
        $this->checkCustomConfigFile($input, $output);

        $symfonyStyle = new SymfonyStyle($input, $output);

        /** @var Config $config */
        $config = $this->getContainer()->get('therat.cron_control.config');

        $symfonyStyle->title('Cron control configuration');

        /** @var ConsoleKernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $customConfigFilename = $kernel->getCustomConfigFilename();
        $full = $input->getOption('full');

        $symfonyStyle->writeln('Custom config filename: '.$customConfigFilename);
        if ($full) {
            $symfonyStyle->writeln(file_get_contents($customConfigFilename));
        }
        $symfonyStyle->section('Glob patterns');
        $symfonyStyle->listing($config->getGlobPatterns());


        $enabledCrontabFiles = $config->getEnabledCrontabFiles();
        $symfonyStyle->section('Enabled crontab');
        $this->show($symfonyStyle, $enabledCrontabFiles, $full);

        $disabledCrontabFiles = $config->getDisabledCrontabFiles();
        $symfonyStyle->section('Disabled crontab');
        $this->show($symfonyStyle, $disabledCrontabFiles, $full);
    }

    protected function show(SymfonyStyle $symfonyStyle, array $files, $full = false)
    {
        if ($files) {
            if ($full) {
                foreach ($files as $file) {
                    $symfonyStyle->section($file);
                    $symfonyStyle->writeln(file_get_contents($file));
                }
            } else {
                $symfonyStyle->listing($files);
            }
        } else {
            $symfonyStyle->listing(['not found']);
        }
    }
}
