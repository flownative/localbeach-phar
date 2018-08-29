<?php
namespace Flownative\Beach\Cli\Command\LocalBeach;

use Flownative\Beach\Cli\Command\BaseCommand;
use Neos\Utility\Exception\FilesException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Start the local beach docker containers.
 */
class StartCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'docker file to use.', '/usr/local/lib/beach-cli/localbeach/docker-compose.yml')
            ->setName('localbeach:start');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $dockerComposeFile = $input->getOption('config');

        $command = 'docker-compose -f ' . escapeshellarg($dockerComposeFile) . ' up -d';
        system($command, $returnValue);

        if ($returnValue === 0) {
            $io->success('Local Beach started!');
        } else {
            $io->error('Local Beach failed to start!');
        }

        return null;
    }
}
