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
     * @param JobModel $job
     * @param string   $filename
     */
    public function __construct(JobModel $job, $filename = 'without_crontab_file')
    {
        $this->job = $job;
        $this->filename = $filename;
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

    public function buildContext()
    {
        $process = $this->getProcess();
        $processOutput = $process->getOutput();
        $processErrorOutput = $process->getErrorOutput();
        $context = [
            'crontab_filename' => $this->getFilename(),
            'schedule' => $this->getJob()->getSchedule(),
            'cmd' => $process->getCommandLine(),
            'exit_code' => $process->getExitCode(),
            'exit_code_text' => $process->getExitCode(),
        ];
        if ($processOutput) {
            $context['output'] = $processOutput;
        }
        if ($processErrorOutput) {
            $context['error_output'] = $processOutput;
        }

        return $context;
    }

    public function hasOutput()
    {
        $process = $this->getProcess();

        return (!$process->isSuccessful() || $process->getOutput());
    }
}
