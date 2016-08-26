<?php
namespace TheRat\CronControl\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use TheRat\ConsoleKernel;

/**
 * Class InitCommand
 * @package TheRat\CronControl\Command
 */
class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Create cron-control config file')
            ->addArgument('dst-config-file', InputArgument::OPTIONAL, 'Config filename');
        $this->addDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('therat.cron_control.config');

        $symfonyStyle = new SymfonyStyle($input, $output);
        $symfonyStyle->title('Cron control make config file');

        $configFilename = $input->getArgument('dst-config-file');
        if (!$configFilename) {
            /** @var ConsoleKernel $kernel */
            $kernel = $this->getContainer()->get('kernel');
            $configFilename = $kernel->getCustomConfigFilename();
        }
        $noInteraction = $input->getOption('no-interaction');

        if (file_exists($configFilename)) {
            throw new \RuntimeException(sprintf('Config file already exists "%s".', $configFilename));
        }

        $config = [];

        $noInteraction ?: $symfonyStyle->section('General');
        $pattern = $symfonyStyle->ask('Enter "glob_patterns"', '~/etc/crontab.conf');
        $config['glob_patterns'] = [$pattern];
        $config['disable_postfix'] = $symfonyStyle->ask('Enter "disable_postfix"', '.disabled');

        $noInteraction ?: $symfonyStyle->section('Logging');
        $config['logger']['level'] = $symfonyStyle->choice(
            'Log level',
            ['DEBUG' => 'DEBUG', 'NOTICE' => 'NOTICE', 'ERROR' => 'ERROR'],
            'NOTICE'
        );
        $config['logger']['filename'] = $symfonyStyle->ask('Log filename', '/tmp/cron-control.log');

        $noInteraction ?: $symfonyStyle->section('Mail');
        $config['mailer']['transport'] = $symfonyStyle->choice(
            'transport',
            ['mail' => 'mail', 'smtp' => 'smtp'],
            'mail'
        );
        $config['mailer']['host'] = $symfonyStyle->ask('host');
        $config['mailer']['port'] = $symfonyStyle->ask('port', 25);
        $config['mailer']['username'] = $symfonyStyle->ask('username');
        $config['mailer']['password'] = $symfonyStyle->ask('password');
        $config['mailer']['sender_name'] = $symfonyStyle->ask('sender_name', 'Cron Control');
        $config['mailer']['sender_email'] = $symfonyStyle->ask('sender_email');

        $content = Yaml::dump(['cron_control' => $config], 3);
        $symfonyStyle->writeln($configFilename);
        $symfonyStyle->writeln($content);
        if ($symfonyStyle->confirm('Confirm generation')) {
            if (file_put_contents($configFilename, $content)) {
                $symfonyStyle->success(sprintf('%s saved', $configFilename));
            }
        }
    }
}
