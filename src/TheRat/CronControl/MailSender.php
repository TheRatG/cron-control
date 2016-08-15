<?php
namespace TheRat\CronControl;

use TheRat\CronControl\Model\ProcessModel;
use TheRat\LoggerTrait;

/**
 * Class MailSender
 * @package TheRat\CronControl
 */
class MailSender
{
    use LoggerTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * MailSender constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param ProcessModel $processModel
     * @return \Swift_Message
     */
    public function generateMessage(ProcessModel $processModel)
    {
        $config = $this->getConfig();
        $host = $config->getHost();
        $job = $processModel->getJob();
        $process = $processModel->getProcess();

        $body = implode(PHP_EOL, [$process->getOutput(), $process->getErrorOutput()]);
        $subject = sprintf('[%s] {job} %s - %s', $host, $job, $process->getExitCode(), $process->getExitCodeText());

        $mail = \Swift_Message::newInstance();
        $mail->setTo($processModel->getJob()->getRecipients());
        $mail->setSubject($subject);
        $mail->setBody($body);
        $mail->setFrom([$config->getSenderEmail() => $config->getSenderName()]);
        $mail->setSender($config->getSenderEmail());

        $processModel->getJob()
            ->buildLogger($this->getConfig()->getLogFilename())
            ->error($subject, $processModel->buildContext());

        return $mail;
    }

    /**
     * @param \Swift_Message $message
     * @return int
     */
    public function send(\Swift_Message $message)
    {
        return $this->getConfig()->getMailer()->send($message);
    }
}
