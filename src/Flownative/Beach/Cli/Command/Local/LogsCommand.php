<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:logs')
            ->addOption('follow', 'f', InputOption::VALUE_NONE, 'Follow log output')
            ->setDescription('Fetch the logs of the Local Beach instance container.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $projectBasePath = LocalHelper::findFlowRootPathStartingFrom(getcwd());
        $localBeachCompose = LocalHelper::getLocalBeachDockerCompose($projectBasePath);

        if (!file_exists($localBeachCompose)) {
            $io->error('We found a Flow or Neos installation but no Local Beach configuration, please run "beach local:init" to get the initial configuration.');
            return 1;
        }

        LocalHelper::loadLocalBeachEnvironment($projectBasePath);
        passthru('docker-compose -f ' . escapeshellarg($localBeachCompose) . ' logs ' . ($input->getOption('follow') ? '--follow ': ''), $returnValue);

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
        }

        return $returnValue;
    }
}
