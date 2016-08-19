<?php
namespace TheRat\CronControl\Model;

use Cron\CronExpression;
use Doctrine\Common\Collections\ArrayCollection;
use TheRat\CronControl\Parser;
use TheRat\LoggerTrait;

/**
 * Class ProcessModelCollection
 * @package TheRat\CronControl\Model
 */
class ProcessModelCollection extends ArrayCollection
{
    use LoggerTrait;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param JobModel $job
     * @param string   $filename
     * @return $this
     */
    public function addJob(JobModel $job, $filename = 'without_crontab_file')
    {
        $processModel = new ProcessModel($job, $filename);

        $cron = CronExpression::factory($job->getSchedule());
        if (!$cron->isDue()) {
            $this->getLogger()->debug('Skipped process by schedule', [(string)$processModel]);
        }

        if (!empty($result[$processModel->getHash()])) {
            throw new \RuntimeException('Found duplicate cron command: '.$processModel);
        }

        $this->set($processModel->getHash(), $processModel);
        $this->getLogger()->debug('Added job process', [(string)$processModel]);

        return $this;
    }

    /**
     * @param $filename
     */
    public function addJobsByCrontab($filename)
    {
        if (is_file($filename) && is_readable($filename)) {
            $jobs = $this->getParser()->generateJobModelsFromString(file_get_contents($filename));

            $this->getLogger()->debug('Found jobs', [count($jobs)]);
            foreach ($jobs as $key => $job) {
                $this->addJob($job, $filename);
            }
        } else {
            $this->getLogger()->error(sprintf('Skipped file "%s", because is not readable', $filename));
        }
    }

    /**
     * @return Parser
     */
    protected function getParser()
    {
        if (!$this->parser) {
            $this->parser = new Parser();
        }

        return $this->parser;
    }
}
