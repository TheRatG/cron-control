<?php
namespace TheRat\CronControlBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DisableCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('disable')
            ->addOption(
                'except',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Do not disable files'
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
        $config = $this->getHelper(ConfigHelper::class)->getConfig();
        $helper = new RunHelper($config);
        $jobs = $helper->generateJobs($config->getGlobPatterns());
        if (count($jobs)) {
            $exceptList = $input->getOption('except');
            foreach (array_keys($jobs) as $filename) {
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
