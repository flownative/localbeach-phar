<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
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
            ->addOption('no-pull', '', InputOption::VALUE_NONE, 'Skip pulling new Docker image versions.')
            ->setDescription('Start the Local Beach instance in this directory.')
            ->setAliases(['local:up']);
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

        $localBeachDockerComposePathAndFilename = LocalHelper::getLocalBeachDockerComposePathAndFilename($projectBasePath);

        if (!file_exists($localBeachDockerComposePathAndFilename)) {
            $io->error('We found a Flow or Neos installation but no Local Beach configuration, please run "beach local:init" to get the initial configuration.');
            return 1;
        }

        LocalHelper::loadLocalBeachEnvironment($projectBasePath);

        if (!$input->getOption('no-pull')) {
            exec('docker-compose -f ' . escapeshellarg($localBeachDockerComposePathAndFilename) . ' pull', $output, $returnValue);
            if ($io->getVerbosity() > 32) {
                $io->listing($output);
            }
            if ($returnValue > 0) {
                $io->error('Something went wrong, check output.');
                return $returnValue;
            }
        }

        exec('docker-compose -f ' . escapeshellarg($localBeachDockerComposePathAndFilename) . ' up --remove-orphans -d', $output, $returnValue);

        if ($io->getVerbosity() > 32) {
            $io->listing($output);
        }

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
            return $returnValue;
        }

        exec('docker exec local_beach_database /bin/bash -c "echo \'CREATE DATABASE IF NOT EXISTS \`' . getenv('BEACH_PROJECT_NAME') . '\`\' | mysql -u root --password=password"');

        if ($io->getVerbosity() > 32) {
            $io->listing($output);
        }

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
        } else {
            $io->success('You are all set');

            $io->text('When files have been synced, you can access this instance at:');
            $io->text('http://' . getenv('BEACH_PROJECT_NAME') . '.localbeach.net');
        }

        return $returnValue;
    }
}
