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
     * @param string       $additionalBody
     * @return \Swift_Message
     */
    public function generateMessage(ProcessModel $processModel, $additionalBody = null)
    {
        $config = $this->getConfig();
        $host = $config->getHost();
        $job = $processModel->getJob();
        $process = $processModel->getProcess();

        $subject = sprintf('[%s] %s', $host, $job);
        if ($process->isStarted()) {
            $body = [$additionalBody, $process->getOutput(), $process->getErrorOutput()];
            $subject .= sprintf(', exit: %s - %s', $process->getExitCode(), $process->getExitCodeText());
        } else {
            $body = [$additionalBody];
        }
        $body = implode(PHP_EOL, array_filter($body));

        $mail = \Swift_Message::newInstance();
        $mail->setTo($processModel->getJob()->getRecipients());
        $mail->setSubject($subject);
        $mail->setBody($body);
        $mail->setFrom([$config->getSenderEmail() => $config->getSenderName()]);
        $mail->setSender($config->getSenderEmail());

        $logger = $processModel->getJob()
            ->buildLogger($this->getConfig()->getLogFilename());
        if ($process->isSuccessful()) {
            $logger->notice($subject, $processModel->buildContext());
        } else {
            $logger->error($subject, $processModel->buildContext());
        }

        return $mail;
    }

    /**
     * @param \Swift_Message $message
     * @return int
     */
    public function send(\Swift_Message $message)
    {
        $result = $this->getConfig()->getMailer()->send($message);

        if (!$result) {
            $this->getLogger()->warning('Email did not send', $message);
        }

        return $result;
    }
}
