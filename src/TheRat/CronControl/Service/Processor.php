<?php
namespace TheRat\CronControl\Service;

use Symfony\Component\Process\Process;
use TheRat\CronControl\Config;
use TheRat\CronControl\MailSender;
use TheRat\CronControl\Model\ProcessModel;
use TheRat\CronControl\Model\ProcessModelCollection;
use TheRat\LoggerTrait;

class Processor
{
    use LoggerTrait;

    /**
     * @var MailSender
     */
    protected $mailSender;

    /**
     * @var ProcessModelCollection
     */
    protected $processModelCollection;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var bool
     */
    protected $shutdownRequested = false;

    public function __construct(Config $config, MailSender $mailSender)
    {
        $this->config = $config;
        $this->mailSender = $mailSender;
        $this->processModelCollection = new ProcessModelCollection();
        $this->processModelCollection->setMailSender($mailSender);
    }

    public function run($period)
    {
        $timeStart = microtime(true);
        do {
            foreach ($this->getProcessModelCollection() as $key => $processModel) {
                /** @var Process $process */
                $process = $processModel->getProcess();

                if (!$process->isStarted()) {
                    if ($this->count() <= $this->getConfig()->getMaxRunningProcesses()) {
                        $process->start();
                        $this->getLogger()->debug(
                            "Process starts",
                            [
                                'crontab_filename' => $processModel->getFilename(),
                                'cmd' => $process->getCommandLine(),
                            ]
                        );
                    } else {
                        $this->getLogger()->error(
                            'Reach max running processes',
                            ['cnt' => $this->getConfig()->getMaxRunningProcesses()]
                        );
                    }
                }

                if (!$process->isRunning()) {
                    if ($processModel->hasOutput()) {
                        if ($process->isSuccessful()) {
                            $this->getLogger()->notice('Process finished with output', $processModel->buildContext());
                        } else {
                            $this->getLogger()->error('Process finished with output', $processModel->buildContext());
                        }

                        $this->sendEmail($processModel);
                    } else {
                        $this->getLogger()->info('Process finished', $processModel->buildContext());
                    }
                    $this->getProcessModelCollection()->remove($key);
                } elseif ($this->shutdownRequested) {
                    $process->stop();
                    $this->getLogger()->debug('Process stopped, shutdown requested', $processModel->buildContext());
                } else {
                    $this->getLogger()->debug('Process still running', $processModel->buildContext());
                }
            }

            $next = false;
            if (time_nanosleep(0, 500000000) === true) {
                $next = true;
            }
            $next = $next && $this->count() > 0;
            $duration = microtime(true) - $timeStart;
            $next = $next && $duration < $period;

            $this->getLogger()->debug(
                'Next iteration',
                [
                    'next' => $next,
                    'process_count' => $this->count(),
                    'time_start' => $timeStart,
                    'duration' => $duration,
                    'period' => $period,
                ]
            );
        } while ($next);

        return $this->count();
    }

    public function count()
    {
        return $this->getProcessModelCollection()->count();
    }

    /**
     * @return ProcessModelCollection
     */
    public function getProcessModelCollection()
    {
        return $this->processModelCollection;
    }

    public function shutdown()
    {
        $this->shutdownRequested = true;

        return $this;
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return MailSender
     */
    protected function getMailSender()
    {
        return $this->mailSender;
    }

    private function sendEmail(ProcessModel $processModel)
    {
        $message = $this->mailSender->generateMessage($processModel);

        return $this->mailSender->send($message);
    }
}
