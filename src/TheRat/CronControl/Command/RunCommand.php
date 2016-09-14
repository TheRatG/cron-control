<?php
namespace TheRat\CronControl\Command;

use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    const OPTION_NAME_ONCE = 'once';
    /**
     * @var bool
     */
    protected $shutdownRequested = false;

    public function run(InputInterface $input, OutputInterface $output)
    {
        // Add the signal handler
        if (function_exists('pcntl_signal')) {
            // Enable ticks for fast signal processing
            declare(ticks = 1);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }

        return parent::run($input, $output);
    }

    /**
     * Handle process signals.
     *
     * @param int $signal The signal code to handle
     */
    public function handleSignal($signal)
    {
        $this->getLogger()->debug('Handle signal', [$signal]);
        switch ($signal) {
            // Shutdown signals
            case SIGTERM:
            case SIGINT:
                $this->shutdown();
                break;
        }
    }

    /**
     * Instruct the command to end the endless loop gracefully.
     *
     * This will finish the current iteration and give the command a chance
     * to cleanup.
     *
     * @return Command The current instance
     */
    public function shutdown()
    {
        $this->shutdownRequested = true;

        return $this;
    }

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Collect crontab tasks and run it')
            ->addOption(
                self::OPTION_NAME_ONCE,
                'o',
                InputOption::VALUE_NONE,
                'Run the command once, usefull for debugging'
            );
        $this->addDryRunOption();
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
        $this->checkCustomConfigFile($input, $output);
        $period = 60;

        $lock = new LockHandler(__FILE__);
        if ($lock->lock()) {
            /** @var Processor $processor */
            $processor = $this->getContainer()->get('therat.cron_control.service.processor');
            $processor->setLogger($this->getLogger());
            $processor->getProcessModelCollection()->setLogger($this->getLogger());

            $once = $input->getOption(self::OPTION_NAME_ONCE);
            do {
                $files = $this->getConfig()->getEnabledCrontabFiles();
                $this->getLogger()->debug('Enabled crontab files', $files);
                foreach ($files as $filename) {
                    $processor->getProcessModelCollection()->addJobsByCrontab($filename);
                }
                $this->getLogger()->debug('Next iteration', ['jobs_count' => $processor->count()]);

                if ($processor->count()) {
                    $processor->run($period);
                }

                if ($this->shutdownRequested) {
                    $processor->shutdown();
                    break;
                }
                $nextIteration = !$once && !$this->shutdownRequested;

                $this->getLogger()->debug('Iteration complete');
            } while ($nextIteration);
        } else {
            $this->getLogger()->debug('The command is already running in another process.');
        }

        $lock->release();
        $this->getLogger()->debug('Finish', ['command_name' => $this->getName()]);

        return 0;
    }
}
