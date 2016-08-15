<?php
namespace TheRat\CronControl\Model;

use Symfony\Component\Process\Process;

/**
 * Class ProcessModel
 * @package TheRat\CronControl\Model
 */
class ProcessModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var JobModel
     */
    protected $job;

    /**
     * @var Process
     */
    protected $process;

    /**
     * ProcessModel constructor.
     * @param string   $filename
     * @param JobModel $job
     */
    public function __construct($filename, JobModel $job)
    {
        $this->filename = $filename;
        $this->job = $job;
    }

    /**
     * @param int $timeout
     * @return Process
     */
    public function getProcess($timeout = 3600)
    {
        if (!$this->process) {
            $process = new Process($this->getJob()->getCommand());
            $process->setTimeout($timeout);
            $process->setIdleTimeout($timeout);

            $this->process = $process;
        }

        return $this->process;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return JobModel
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->getFilename().':'.$this->getJob()->getHash();
    }

    public function __toString()
    {
        $result = sprintf(
            "{'filename':'%s', 'schedule':'%s', 'cmd': '%s'}",
            $this->getFilename(),
            $this->getJob()->getSchedule(),
            $this->getProcess()->getCommandLine()
        );

        return $result;
    }
}
