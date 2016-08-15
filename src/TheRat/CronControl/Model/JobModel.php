<?php
namespace TheRat\CronControl\Model;

use Monolog\Handler\StreamHandler;
use Symfony\Bridge\Monolog\Logger;

class JobModel extends AbstractModel
{
    /**
     * @var $regex
     */
    static public $regex = [
        'minute' => '/[\*,\/\-0-9]+/',
        'hour' => '/[\*,\/\-0-9]+/',
        'dayOfMonth' => '/[\*,\/\-\?LW0-9A-Za-z]+/',
        'month' => '/[\*,\/\-0-9A-Z]+/',
        'dayOfWeek' => '/[\*,\/\-0-9A-Z]+/',
        'command' => '/^(.)*$/',
    ];
    /**
     * @var string
     */
    protected $minute = '0';
    /**
     * @var string
     */
    protected $hour = '*';
    /**
     * @var string
     */
    protected $dayOfMonth = '*';
    /**
     * @var string
     */
    protected $month = '*';
    /**
     * @var string
     */
    protected $dayOfWeek = '*';
    /**
     * @var string
     */
    protected $command = null;
    /**
     * @var string
     */
    protected $comments = null;
    /**
     * @var string
     */
    protected $logFile = null;
    /**
     * @var string
     */
    protected $logSize = null;
    /**
     * @var string
     */
    protected $errorFile = null;
    /**
     * @var string
     */
    protected $errorSize = null;
    /**
     * @var \DateTime
     */
    protected $lastRunTime = null;
    /**
     * @var string
     */
    protected $status = 'unknown';
    /**
     * @var $hash
     */
    protected $hash = null;
    /**
     * @var string[]
     */
    protected $recipients = [];
    /**
     * @var string
     */
    protected $outputLogFilename;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Parse crontab line into self object
     *
     * @param string $jobLine
     *
     * @return self
     * @throws \InvalidArgumentException
     */
    static public function parse($jobLine)
    {
        // split the line
        $parts = preg_split('@ @', $jobLine, null, PREG_SPLIT_NO_EMPTY);

        // check the number of part
        if (count($parts) < 5) {
            throw new \InvalidArgumentException('Wrong job number of arguments.');
        }

        // analyse command
        $command = implode(' ', array_slice($parts, 5));

        // prepare variables
        $lastRunTime = $logFile = $logSize = $errorFile = $errorSize = $comments = null;

        // extract comment
        if (strpos($command, '#')) {
            list($command, $comment) = explode('#', $command);
            $comments = trim($comment);
        }

        // extract error file
        if (strpos($command, '2>>')) {
            list($command, $errorFile) = explode('2>>', $command);
            $errorFile = trim($errorFile);
        }

        // extract log file
        if (strpos($command, '>>')) {
            list($command, $logPart) = explode('>>', $command);
            $logPart = explode(' ', trim($logPart));
            $logFile = trim($logPart[0]);
        }

        // compute last run time, and file size
        if (isset($logFile) && file_exists($logFile)) {
            $lastRunTime = filemtime($logFile);
            $logSize = filesize($logFile);
        }
        if (isset($errorFile) && file_exists($errorFile)) {
            $lastRunTime = max($lastRunTime ?: 0, filemtime($errorFile));
            $errorSize = filesize($errorFile);
        }

        $command = trim($command);

        // compute status
        $status = 'error';
        if ($logSize === null && $errorSize === null) {
            $status = 'unknown';
        } else {
            if ($errorSize === null || $errorSize == 0) {
                $status = 'success';
            }
        }

        // set the self object
        $job = new self();
        $job
            ->setMinute($parts[0])
            ->setHour($parts[1])
            ->setDayOfMonth($parts[2])
            ->setMonth($parts[3])
            ->setDayOfWeek($parts[4])
            ->setCommand($command)
            ->setErrorFile($errorFile)
            ->setErrorSize($errorSize)
            ->setLogFile($logFile)
            ->setLogSize($logSize)
            ->setComments($comments)
            ->setLastRunTime($lastRunTime)
            ->setStatus($status);

        return $job;
    }

    /**
     * @param string $defaultLogFilename
     * @return Logger
     */
    public function buildLogger($defaultLogFilename)
    {
        $logger = new Logger(get_called_class());
        $logFilename = $this->getOutputLogFilename() ?: $defaultLogFilename;
        $logger->pushHandler(new StreamHandler($logFilename));

        return $logger;
    }

    /**
     * @param Logger $logger
     * @return self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputLogFilename()
    {
        return $this->outputLogFilename;
    }

    /**
     * @param string $outputLogFilename
     * @return self
     */
    public function setOutputLogFilename($outputLogFilename)
    {
        $this->outputLogFilename = $outputLogFilename;

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @param \string[] $recipients
     * @return self
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Return the minute
     *
     * @return string
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Set the minute (* 1 1-10,11-20,30-59 1-59 *\/1)
     *
     * @param string $minute
     *
     * @return self
     */
    public function setMinute($minute)
    {
        if (!preg_match(self::$regex['minute'], $minute)) {
            throw new \InvalidArgumentException(sprintf('Minute "%s" is incorrect', $minute));
        }

        $this->minute = $minute;

        return $this->generateHash();
    }

    /**
     * Return the hour
     *
     * @return string
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Set the hour
     *
     * @param string $hour
     *
     * @return self
     */
    public function setHour($hour)
    {
        if (!preg_match(self::$regex['hour'], $hour)) {
            throw new \InvalidArgumentException(sprintf('Hour "%s" is incorrect', $hour));
        }

        $this->hour = $hour;

        return $this->generateHash();
    }

    /**
     * Return the day of month
     *
     * @return string
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Set the day of month
     *
     * @param string $dayOfMonth
     *
     * @return self
     */
    public function setDayOfMonth($dayOfMonth)
    {
        if (!preg_match(self::$regex['dayOfMonth'], $dayOfMonth)) {
            throw new \InvalidArgumentException(sprintf('DayOfMonth "%s" is incorrect', $dayOfMonth));
        }

        $this->dayOfMonth = $dayOfMonth;

        return $this->generateHash();
    }

    /**
     * Return the month
     *
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set the month
     *
     * @param string $month
     *
     * @return self
     */
    public function setMonth($month)
    {
        if (!preg_match(self::$regex['month'], $month)) {
            throw new \InvalidArgumentException(sprintf('Month "%s" is incorrect', $month));
        }

        $this->month = $month;

        return $this->generateHash();
    }

    /**
     * Return the day of week
     *
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Set the day of week
     *
     * @param string $dayOfWeek
     *
     * @return self
     */
    public function setDayOfWeek($dayOfWeek)
    {
        if (!preg_match(self::$regex['dayOfWeek'], $dayOfWeek)) {
            throw new \InvalidArgumentException(sprintf('DayOfWeek "%s" is incorrect', $dayOfWeek));
        }

        $this->dayOfWeek = $dayOfWeek;

        return $this->generateHash();
    }

    /**
     * Return the command
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set the command
     *
     * @param string $command
     *
     * @return self
     */
    public function setCommand($command)
    {
        if (!preg_match(self::$regex['command'], $command)) {
            throw new \InvalidArgumentException(sprintf('Command "%s" is incorrect', $command));
        }

        $this->command = $command;

        return $this->generateHash();
    }

    /**
     * Return the status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the status
     *
     * @param string $status
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Return the comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set the comments
     *
     * @param string $comments
     *
     * @return self
     */
    public function setComments($comments)
    {
        if (is_array($comments)) {
            $comments = implode($comments, ' ');
        }

        $this->comments = $comments;

        return $this;
    }

    /**
     * Return error file
     *
     * @return string
     */
    public function getErrorFile()
    {
        return $this->errorFile;
    }

    /**
     * Set the error file
     *
     * @param string $errorFile
     *
     * @return self
     */
    public function setErrorFile($errorFile)
    {
        $this->errorFile = $errorFile;

        return $this->generateHash();
    }

    /**
     * Return the error file size
     *
     * @return string
     */
    public function getErrorSize()
    {
        return $this->errorSize;
    }

    /**
     * Set the error file size
     *
     * @param string $errorSize
     *
     * @return self
     */
    public function setErrorSize($errorSize)
    {
        $this->errorSize = $errorSize;

        return $this;
    }

    /**
     * Return log file
     *
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * Set the log file
     *
     * @param string $logFile
     *
     * @return self
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;

        return $this->generateHash();
    }

    /**
     * Return the log file size
     *
     * @return string
     */
    public function getLogSize()
    {
        return $this->logSize;
    }

    /**
     * Set the log file size
     *
     * @param string $logSize
     *
     * @return self
     */
    public function setLogSize($logSize)
    {
        $this->logSize = $logSize;

        return $this;
    }

    /**
     * Return the job unique hash
     *
     * @return string
     */
    public function getHash()
    {
        if (null === $this->hash) {
            $this->generateHash();
        }

        return $this->hash;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Exception $e) {
            return '# '.$e;
        }
    }

    /**
     * Get an array of job entries
     *
     * @return array
     */
    public function getEntries()
    {
        return [
            $this->getMinute(),
            $this->getHour(),
            $this->getDayOfMonth(),
            $this->getMonth(),
            $this->getDayOfWeek(),
            $this->getCommand(),
            $this->prepareLog(),
            $this->prepareError(),
            $this->prepareComments(),
        ];
    }

    /**
     * @return string
     */
    public function getSchedule()
    {
        return implode(
            ' ',
            [
                $this->getMinute(),
                $this->getHour(),
                $this->getDayOfMonth(),
                $this->getMonth(),
                $this->getDayOfWeek(),
            ]
        );
    }

    /**
     * Render the job for crontab
     *
     * @return string
     */
    public function render()
    {
        if (null === $this->getCommand()) {
            throw new \InvalidArgumentException('You must specify a command to run.');
        }

        // Create / Recreate a line in the crontab
        $line = trim(implode(' ', $this->getEntries()));

        return $line;
    }

    /**
     * Prepare comments
     *
     * @return string or null
     */
    public function prepareComments()
    {
        if (null !== $this->getComments()) {
            return '# '.$this->getComments();
        } else {
            return null;
        }
    }

    /**
     * Prepare log
     *
     * @return string or null
     */
    public function prepareLog()
    {
        if (null !== $this->getLogFile()) {
            return '>> '.$this->getLogFile();
        } else {
            return null;
        }
    }

    /**
     * Prepare log
     *
     * @return string or null
     */
    public function prepareError()
    {
        if (null !== $this->getErrorFile()) {
            return '2>> '.$this->getErrorFile();
        } else {
            if ($this->prepareLog()) {
                return '2>&1';
            } else {
                return null;
            }
        }
    }

    /**
     * Return the error file content
     *
     * @return string
     */
    public function getErrorContent()
    {
        if ($this->getErrorFile() && file_exists($this->getErrorFile())) {
            return file_get_contents($this->getErrorFile());
        } else {
            return null;
        }
    }

    /**
     * Return the log file content
     *
     * @return string
     */
    public function getLogContent()
    {
        if ($this->getLogFile() && file_exists($this->getLogFile())) {
            return file_get_contents($this->getLogFile());
        } else {
            return null;
        }
    }

    /**
     * Return the last job run time
     *
     * @return \DateTime|null
     */
    public function getLastRunTime()
    {
        return $this->lastRunTime;
    }

    /**
     * Set the last job run time
     * @param string $lastRunTime
     *
     * @return $this
     */
    public function setLastRunTime($lastRunTime)
    {
        $this->lastRunTime = \DateTime::createFromFormat('U', $lastRunTime);

        return $this;
    }

    /**
     * Generate a unique hash related to the job entries
     *
     * @return self
     */
    private function generateHash()
    {
        $this->hash = implode(
            '_',
            [
                strval($this->getMinute()),
                strval($this->getHour()),
                strval($this->getDayOfMonth()),
                strval($this->getMonth()),
                strval($this->getDayOfWeek()),
                strval($this->getCommand()),
                strval($this->getErrorFile()),
            ]
        );

        return $this;
    }
}
