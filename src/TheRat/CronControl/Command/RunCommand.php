<?php
namespace TheRat\CronControl\Command;

use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use TheRat\CronControl\Service\Processor;

/**
 * Class RunCommand
 * @package TheRat\CronControl\Command
 */
class RunCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Collect crontab tasks and run it');
    }

    /**
     * @param InputInterface         $input
     * @param OutputInterface|Output $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLogger()->pushHandler(new ConsoleHandler($output));
        $this->getLogger()->debug(
            'Start',
            [
                'command_name' => $this->getName(),
                'args' => $input->getArguments(),
                'opts' => $input->getOptions(),
            ]
        );
        $period = 60;

        $lock = new LockHandler($this->getName());
        if ($lock->lock()) {
            /** @var Processor $processor */
            $processor = $this->getContainer()->get('therat.cron_control.service.processor');
            $processor->setLogger($this->getLogger());

            do {
                $timeStart = microtime(true);

                $files = $this->getConfig()->getEnabledCrontabFiles();
                $this->getLogger()->debug('Enabled crontab files', $files);
                foreach ($files as $filename) {
                    $processor->getProcessModelCollection()->addJobsByCrontab($filename);
                }
                $this->getLogger()->debug('Next iteration', ['jobs_count' => $processor->count()]);

                if ($processor->count()) {
                    $processor->run();

                    $duration = microtime(true) - $timeStart;
                    if ($duration < $period) {
                        $sleep = intval($period - $duration);
                        $this->getLogger()->debug('Sleep...', ['sec' => $sleep]);
                        sleep($sleep);
                    }
                }
            } while ($processor->count() > 0);
        } else {
            $this->getLogger()->debug('The command is already running in another process.');
        }

        $this->getLogger()->debug('Finish', ['command_name' => $this->getName()]);

        return 0;
    }
}
