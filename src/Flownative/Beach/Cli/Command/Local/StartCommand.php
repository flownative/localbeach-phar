<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:start')
            ->setDescription('Start the Local Beach instance in this directory.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $projectBasePath = LocalHelper::findFlowRootPathStartingFrom(getcwd());

        $localBeachCompose = LocalHelper::getLocalBeachDockerCompose($projectBasePath);

        if (!file_exists($localBeachCompose)) {
            $io->error('We found a Flow or Neos installation but no Local Beach configuration, please run "beach local:init" to get the intial configuration.');
            return 1;
        }

        LocalHelper::loadLocalBeachEnvironment($projectBasePath);

        exec('docker-compose -f ' . escapeshellarg($localBeachCompose) . ' up -d', $output, $returnValue);

        if ($io->getVerbosity() > 32) {
            $io->listing($output);
        }

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
        } else {
            $io->success('You are all set');

            $io->text('Access this instance at:');
            $io->text('http://' . getenv('BEACH_PROJECT_NAME') . '.localbeach.net');
        }

        return $returnValue;
    }
}