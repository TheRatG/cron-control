<?php
namespace TheRat\CronControl\Command;

use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use TheRat\CronControl\Service\Switcher;

class EnableCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('enable')
            ->addOption(
                'except',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Do not disable files preg match expression'
            )
            ->setDescription('Rename crontab files for enable');
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

        /** @var Switcher $switcher */
        $switcher = $this->getContainer()->get('therat.cron_control.service.switcher');
        $switcher->setLogger($this->getLogger());

        $files = $this->getConfig()->getDisabledCrontabFiles();
        if (count($files)) {
            $exceptions = $input->getOption('except');
            $switcher->enableFiles($files, $exceptions, $input->getOption('dry-run'));
        } else {
            $this->getLogger()->debug('Files not found');
        }

        $this->getLogger()->debug('Finish', ['command_name' => $this->getName()]);

        return 0;
    }
}
