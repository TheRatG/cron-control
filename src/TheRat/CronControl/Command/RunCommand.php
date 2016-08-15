<?php
namespace TheRat\CronControl\Command;

use Cron\CronExpression;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use TheRat\CronControl\MailSender;
use TheRat\CronControl\Model\ProcessModel;
use TheRat\CronControl\Parser;

/**
 * Class RunCommand
 * @package TheRat\CronControl\Command
 */
class RunCommand extends AbstractCommand
{
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
                    if ($processModel->hasOutput()) {
                        $this->getLogger()->error('Process stopped', $processModel->buildContext());

                        $send = $this->sendEmail($processModel);
                        if (!$send) {
                            $this->getLogger()->warning('Email did not send');
                        }
                    } else {
                        $this->getLogger()->debug('Process stopped', $processModel->buildContext());
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

    private function sendEmail(ProcessModel $processModel)
    {
        /** @var MailSender $mailSender */
        $mailSender = $this->getContainer()->get('therat.cron_control.mail_sender');
        $message = $mailSender->generateMessage($processModel);

        return $mailSender->send($message);
    }
}
