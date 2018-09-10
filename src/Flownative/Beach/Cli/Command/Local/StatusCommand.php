<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:status')
            ->setDescription('Show status of the Local Beach instance container.');
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
        passthru('docker-compose -f ' . escapeshellarg($localBeachDockerComposePathAndFilename) . ' ps ', $returnValue);

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
        }

        return $returnValue;
    }
}
