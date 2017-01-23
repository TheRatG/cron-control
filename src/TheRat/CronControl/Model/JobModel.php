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
     * @var \DateTime
     */
    protected $lastRunTime = null;
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
     * Parse crontab line into self object
     *
     * @param string $jobLine
     *
     * @return self
     * @throws \InvalidArgumentException
     */
    static public function parse($jobLine)
    {
        $jobLine = preg_replace('/\s+/', ' ', $jobLine, 5);
        // split the line
        $parts = preg_split('@ @', $jobLine, null, PREG_SPLIT_NO_EMPTY);

        // check the number of part
        if (count($parts) < 5) {
            throw new \InvalidArgumentException('Wrong job number of arguments.');
        }

        // analyse command
        $command = implode(' ', array_slice($parts, 5));

        // prepare variables
        $lastRunTime = $comments = null;

        // extract comment
        if (strpos($command, '#')) {
            list($command, $comment) = explode('#', $command);
            $comments = trim($comment);
        }
        $command = trim($command);

        // set the self object
        $job = new self();
        $job
            ->setMinute($parts[0])
            ->setHour($parts[1])
            ->setDayOfMonth($parts[2])
            ->setMonth($parts[3])
            ->setDayOfWeek($parts[4])
            ->setCommand($command)
            ->setComments($comments)
            ->setLastRunTime($lastRunTime);

        return $job;
    }

    /**
     * @param string $defaultLogFilename
     * @return Logger
     */
    public function buildLogger($defaultLogFilename)
    {
        $logFilename = $this->getOutputLogFilename() ?: $defaultLogFilename;
        $logger = new Logger('CHILD');
        $logger->pushHandler(new StreamHandler($logFilename));

        return $logger;
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
            ]
        );

        return $this;
    }
}
