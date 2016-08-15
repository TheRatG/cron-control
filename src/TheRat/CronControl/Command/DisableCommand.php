<?php
namespace TheRat\CronControl\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TheRat\CronControl\Config;

class DisableCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('disable')
            ->addOption(
                'except',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Do not disable files preg match expression'
            )
            ->setDescription('Rename crontab files for disable');
    }

    /**
     * @param InputInterface         $input
     * @param OutputInterface|Output $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        /** @var Config $config */
        $config = $this->getContainer()->get('therat.cron_control.config');

        $files = $config->getEnabledCrontabFiles();
        if (count($files)) {
            $exceptList = $input->getOption('except');
            foreach ($files as $filename) {
                $except = $this->isInExceptionList($filename, $exceptList);
                if ($except) {
                    $symfonyStyle->writeln(
                        sprintf('Skipped disable "%s", because except "%s"', $filename, $except)
                    );
                    continue;
                }

                $newFilename = $filename.$config->getDisablePostfix();
                $symfonyStyle->writeln(sprintf('%s -> %s', $filename, $newFilename));
                if (!$input->getOption('dry-run')) {
                    //todo check filetime
                    rename($filename, $newFilename);
                }
            }
        } else {
            $symfonyStyle->writeln('Files not found');
        }
    }

    protected function isInExceptionList($filename, array $exceptList)
    {
        $result = false;
        if (!empty($exceptList)) {
            foreach ($exceptList as $except) {
                if (preg_match('#'.preg_quote($except, '#').'#', $filename)) {
                    $result = $except;
                    break;
                }
            }
        }

        return $result;
    }
}
