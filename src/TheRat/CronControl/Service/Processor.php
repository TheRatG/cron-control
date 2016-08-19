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

    public function __construct(Config $config, MailSender $mailSender)
    {
        $this->config = $config;
        $this->mailSender = $mailSender;
        $this->processModelCollection = new ProcessModelCollection();
    }

    public function run()
    {
        do {
            foreach ($this->getProcessModelCollection() as $key => $processModel) {
                /** @var Process $process */
                $process = $processModel->getProcess();

                if (!$process->isStarted()) {
                    if ($this->getProcessModelCollection()->count() <= $this->getConfig()->getMaxRunningProcesses()) {
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
                        $this->getLogger()->error('Process stopped', $processModel->buildContext());

                        $send = $this->sendEmail($processModel);
                        if (!$send) {
                            $this->getLogger()->warning('Email did not send');
                        }
                    } else {
                        $this->getLogger()->debug('Process stopped', $processModel->buildContext());
                    }
                    $this->getProcessModelCollection()->remove($key);
                }
            }
        } while ($this->getProcessModelCollection()->count() > 0);
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
