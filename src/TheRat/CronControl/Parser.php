<?php
namespace TheRat\CronControl;

use TheRat\CronControl\Model\AbstractModel;
use TheRat\CronControl\Model\JobModel;
use TheRat\CronControl\Model\VariableModel;

/**
 * Class Parser
 * @package TheRat\CronControl
 */
class Parser
{
    /**
     * Returns an array of Cron JobModels based on the contents of a file.
     *
     * @param string $input
     *
     * @return AbstractModel[]
     */
    public function parseString($input)
    {
        $elements = [];

        $lines = array_filter(
            explode(PHP_EOL, $input),
            function ($line) {
                return '' != trim($line);
            }
        );

        foreach ($lines as $line) {
            $trimmed = trim($line);
            // if line is not a comment, convert it to a cron
            if (0 !== \strpos($trimmed, '#')) {
                if (preg_match('/^[^\s]+\s?=/', $line)) {
                    $elements[] = VariableModel::parse($line);
                } else {
                    $elements[] = JobModel::parse($line);
                }
            }
        }

        return $elements;
    }

    /**
     * @param string $input
     *
     * @return JobModel[]
     */
    public function generateJobModelsFromString($input)
    {
        $emails = [];
        $variables = [];

        /** @var JobModel[] $jobs */
        $jobs = [];

        $elements = $this->parseString($input);
        $logFilename = null;
        $emails = [];
        foreach ($elements as $element) {
            if ($element instanceof VariableModel) {
                switch ($element->getName()) {
                    case VariableModel::NAME_MAIL:
                        $emails += explode(',', $element->getValue());
                        break;
                    case VariableModel::NAME_LOG_FILENAME:
                        $logFilename = $element->getValue();
                        break;
                    default:
                        $variables[] = $element->getName().'='.$element->getValue();
                }
            } elseif ($element instanceof JobModel) {
                if (!array_key_exists($element->getHash(), $jobs)) {
                    $jobs[$element->getHash()] = $element;
                }
            }
        }

        $emails = array_filter($emails);
        $envCommand = $this->makeEnvCommand($variables);

        $result = [];
        foreach ($jobs as $key => $job) {
            if ($envCommand) {
                $job->setCommand($envCommand.'; '.$job->getCommand());
            }
            if ($emails) {
                $job->setRecipients($job->getRecipients() + $emails);
            }
            if ($logFilename) {
                $job->setOutputLogFilename($logFilename);
            }
            $result[$job->getHash()] = $job;
        }

        return $result;
    }

    /**
     * @param array $variables
     * @return string
     */
    protected function makeEnvCommand(array $variables)
    {
        $variables = array_filter($variables);
        $variables = array_unique($variables);
        $result = [];
        foreach ($variables as $value) {
            $result[] = 'export '.$value;
        }
        $result = implode('; ', $result);

        return $result;
    }
}
