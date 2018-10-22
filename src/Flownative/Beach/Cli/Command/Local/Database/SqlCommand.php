<?php
namespace Flownative\Beach\Cli\Command\Local\Database;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SqlCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:database:sql')
            ->setDescription('Send SQL commands to this projects database.')
            ->setHelp(
                ""
            )
            ->addArgument('filename', InputOption::VALUE_REQUIRED, 'The file to load SQL from, alternatively provide a "-" and pipe input.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $projectBasePath = LocalHelper::findFlowRootPathStartingFrom(getcwd());
        } catch (\Exception $exception) {
            $io->error(sprintf('Could not find Flow root path starting from %s.', getcwd()));
            return 1;
        }
        LocalHelper::loadLocalBeachEnvironment($projectBasePath);

        $filename = $input->getArgument('filename');
        if ($filename === '-') {
            $filename = 'php://stdin';
        }

        if (empty($filename)) {
            $io->error(sprintf('No filename given.'));
            return 1;
        }

        $handle = fopen($filename, 'r');
        $descriptorspec = [$handle, ["pipe", "w"]];

        $sshCommand = 'ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -p ' . getenv('BEACH_SSH_PORT') . '  beach@' . getenv('BEACH_PROJECT_NAME') . '.localbeach.net';
        $quotedMySqlCommand = '\'mysql -h $BEACH_DATABASE_HOST ' . getenv('BEACH_PROJECT_NAME') . '\'';

        $process = proc_open($sshCommand . ' ' . $quotedMySqlCommand, $descriptorspec, $pipes, $projectBasePath);

        if (!$process) {
            $io->error('Something went wrong!');
            return 1;
        }

        fclose($handle);

        if ($io->getVerbosity() > 32) {
            echo stream_get_contents($pipes[1]);
        }
        fclose($pipes[1]);
        $return_value = proc_close($process);

        if ($return_value !== 0) {
            $io->error('Something went wrong, check verbose output.');
        } else {
            $io->success('Success');
        }

        return $return_value;
    }
}
