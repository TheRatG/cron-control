<?php
namespace TheRat\CronControl;

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class Config
 * @package TheRat\CronControl
 */
class Config
{
    /**
     * @var string
     */
    protected $disablePostfix;

    /**
     * @var string[]
     */
    protected $globPatterns;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $senderName;

    /**
     * @var string
     */
    protected $senderEmail;

    /**
     * @var string
     */
    protected $logFilename;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->disablePostfix = $config['disable_postfix'];
        $this->globPatterns = $config['glob_patterns'];
        $this->mailer = $this->buildMailer($config['mailer']);
        $this->senderName = $config['mailer']['sender_name'];
        $this->senderEmail = $config['mailer']['sender_email'];
    }

    /**
     * @return string
     */
    public function getLogFilename()
    {
        return $this->logFilename;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = new Logger('MAIN');
            if ($this->getLogFilename()) {
                $this->logger->pushHandler(new StreamHandler($this->getLogFilename()));
            } else {
                $this->logger->pushHandler(new NullHandler());
            }
        }

        return $this->logger;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @return mixed
     */
    public function getGlobPatterns()
    {
        return $this->globPatterns;
    }

    /**
     * @return array
     */
    public function getDisabledGlobPatterns()
    {
        $patterns = $this->getGlobPatterns();
        $postfix = $this->getDisablePostfix();
        $result = array_map(
            function ($item) use ($postfix) {
                return $item.$postfix;
            },
            $patterns
        );

        return $result;
    }

    /**
     * @return mixed
     */
    public function getDisablePostfix()
    {
        return $this->disablePostfix;
    }

    /**
     * @return array
     */
    public function getEnabledCrontabFiles()
    {
        return $this->getCrontabFiles($this->getGlobPatterns());
    }

    /**
     * @return array
     */
    public function getDisabledCrontabFiles()
    {
        return $this->getCrontabFiles($this->getDisabledGlobPatterns());
    }

    /**
     * @param array $patterns
     * @return array
     */
    public function getCrontabFiles(array $patterns)
    {
        $result = [];
        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            foreach ($files as $filename) {
                if (is_file($filename) && is_readable($filename)) {
                    $result[] = $filename;
                }
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return php_uname('n');
    }

    public function getSenderEmail()
    {
        return $this->senderEmail ?: 'cron-control'.'@'.$this->getHost();
    }

    public function getSenderName()
    {
        return $this->senderName ?: 'cron-control';
    }

    public function getMaxRunningProcesses()
    {
        return 1000;
    }

    private function buildMailer(array $config)
    {
        if ($config['transport'] == 'smtp') {
            $transport = \Swift_SmtpTransport::newInstance(
                $config['host'],
                $config['port'],
                $config['security']
            );
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
        } elseif ($config['transport'] == 'mail') {
            $transport = \Swift_MailTransport::newInstance();
        } else {
            $transport = \Swift_SendmailTransport::newInstance();
        }

        return \Swift_Mailer::newInstance($transport);
    }
}
