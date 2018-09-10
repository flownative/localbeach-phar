<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:down')
            ->setDescription('Stop the Local Beach instance in this directory and remove its containers and volume.');
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

        exec('docker-compose -f ' . escapeshellarg($localBeachDockerComposePathAndFilename) . ' down --remove-orphans --volumes', $output, $returnValue);

        if ($io->getVerbosity() > 32) {
            $io->listing($output);
        }

        if ($returnValue > 0) {
            $io->error('Something went wrong, check output.');
        } else {
            $io->success('Local instance shut down.');
        }

        return $returnValue;
    }
}
