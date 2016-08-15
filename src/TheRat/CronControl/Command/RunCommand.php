<?php
namespace TheRat\CronControl\Command;

use Cron\CronExpression;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use TheRat\CronControl\Model\ProcessModel;
use TheRat\CronControl\Parser;
use TheRat\LoggerTrait;

/**
 * Class RunCommand
 * @package TheRat\CronControl\Command
 */
class RunCommand extends AbstractCommand
{
    use LoggerTrait;

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Collect crontab tasks and run it');
    }

    /**
     * @param InputInterface         $input
     * @param OutputInterface|Output $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLogger()->pushHandler(new ConsoleHandler($output));

        $processModelList = $this->getProcesses();
        $this->getLogger()->debug('Total processes', [count($processModelList)]);
        do {
            foreach ($processModelList as $key => $processModel) {
                $process = $processModel->getProcess();

                if (!$process->isStarted()) {
                    $process->start();

                    $this->getLogger()->debug(
                        "Process starts",
                        [
                            'crontab_filename' => $processModel->getFilename(),
                            'cmd' => $process->getCommandLine(),
                        ]
                    );
                }


                if (!$process->isRunning()) {
                    $this->getLogger()->debug(
                        "Process stopped",
                        ['crontab_filename' => $processModel->getFilename(), 'cmd' => $process->getCommandLine()]
                    );

                    if ($process->isSuccessful()) {
                        $this->getLogger()->debug(
                            'Output',
                            [
                                'crontab_filename' => $processModel->getFilename(),
                                'cmd' => $process->getCommandLine(),
                                'output' => $process->getOutput(),
                            ]
                        );
                    } else {
                        $this->getLogger()->error(
                            'Process error',
                            [
                                'crontab_filename' => $processModel->getFilename(),
                                'cmd' => $process->getCommandLine(),
                                'code' => $process->getExitCode(),
                                'code_text' => $process->getExitCodeText(),
                                'error' => $process->getErrorOutput(),
                            ]
                        );
                    }

                    unset($processModelList[$key]);
                }
            }

            $countProcesses = count($processModelList);
        } while ($countProcesses > 0);
    }

    /**
     * @return ProcessModel[]
     */
    private function getProcesses()
    {
        $parser = new Parser();
        $files = $this->getConfig()->getEnabledCrontabFiles();
        $this->getLogger()->debug('Enabled crontab files', $files);

        $result = [];
        foreach ($files as $filename) {
            if (is_file($filename) && is_readable($filename)) {
                $jobs = $parser->generateJobModelsFromString(file_get_contents($filename));

                $this->getLogger()->debug('Found jobs', [count($jobs)]);
                foreach ($jobs as $key => $job) {
                    $processModel = new ProcessModel($filename, $job);

                    $cron = CronExpression::factory($job->getSchedule());
                    if (!$cron->isDue()) {
                        $this->getLogger()->debug('Skipped process by schedule', [(string)$processModel]);

                        continue;
                    }

                    if (!empty($result[$processModel->getHash()])) {
                        throw new \RuntimeException('Found duplicate cron command: '.$processModel);
                    }

                    $result[$processModel->getHash()] = $processModel;

                    $this->getLogger()->debug('Added process', [(string)$processModel]);
                }
            } else {
                $this->getLogger()->error(sprintf('Skipped file "%s", because is not readable', $filename));
            }
        }

        return $result;
    }
}
