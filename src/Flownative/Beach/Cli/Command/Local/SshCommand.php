<?php
namespace Flownative\Beach\Cli\Command\Local;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SshCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:ssh')
            ->setDescription('Login in to a Local Beach instance via SSH.');
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

        if (!$this->isTerminal(STDIN)) {
            $io->error('This command currently only supports interactive terminal sessions.');
        }

        $command =
            'ssh ' .
            ($this->isTerminal(STDIN) ? '-t ' : '') .
            '-p ' . getenv('BEACH_SSH_PORT') . ' ' .
            'beach@localbeach.net';

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
        return proc_close($process);
    }

    /**
     * @param resource $descriptor
     * @return bool
     */
    private function isTerminal($descriptor): bool
    {
        return !function_exists('posix_isatty') || posix_isatty($descriptor);

    }
}
