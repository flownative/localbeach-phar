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

class InitCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('local:init')
            ->setDescription('Initialize a Flow distribution as a Local Beach project')
            ->setHelp(
"The <info>local:init</info> command creates a Local Beach configuration for a
local Flow or Neos project, so you can develop locally using the same Docker images for
Nginx, PHP and Redis which later be used in the cloud.
"
            )
            ->addOption('projectName', 'p', InputOption::VALUE_REQUIRED, 'Project name. Must be DNS compatible - use only characters, numbers and hyphens, e.g. "my-new-website"')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force initialization. If set, any existing Docker Compose and Local Beach configuration will be overwritten')
            ->addOption('createDatabase', 'd', InputOption::VALUE_NONE, 'Create a new database on the Local Beach Maria DB server');
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

        $projectName = $input->getOption('projectName') ?: basename($projectBasePath);
        $projectName = preg_replace('/[^a-zA-Z0-9-]/', '', $projectName);

        $localBeachDockerComposePathAndFilename = LocalHelper::getLocalBeachDockerComposePathAndFilename($projectBasePath);
        $localBeachDistributionEnvironment = LocalHelper::getLocalBeachDistributionEnvironmentFilePath($projectBasePath);

        if (!file_exists($localBeachDockerComposePathAndFilename) || $input->getOption('force')) {
            copy(CLI_ROOT_PATH . 'resources/docker-compose.yml', $localBeachDockerComposePathAndFilename);
        } else {
            $io->error($localBeachDockerComposePathAndFilename . ' already exists');
            $io->text('This command would create a new ' . basename($localBeachDockerComposePathAndFilename) . ', please use --force to overwrite the existing file.');
        }

        try {
            Files::createDirectoryRecursively($projectBasePath . 'Configuration/Development/Beach');
        } catch (FilesException $e) {
        }

        if (!file_exists($projectBasePath . 'Configuration/Development/Beach/Settings.yaml') || $input->getOption('force')) {
            copy(CLI_ROOT_PATH . 'resources/Settings.yaml', $projectBasePath . 'Configuration/Development/Beach/Settings.yaml');
        } else {
            $io->error($projectBasePath . 'Configuration/Development/Beach/Settings.yaml already exists');
            $io->text('This command would create a new Settings.yaml, please use --force to overwrite the existing file.');
        }

        if (!file_exists($localBeachDistributionEnvironment) || $input->getOption('force')) {
            file_put_contents($localBeachDistributionEnvironment, str_replace('${BEACH_PROJECT_NAME}', $projectName, file_get_contents(CLI_ROOT_PATH . 'resources/.env')));
        } else {
            $io->error($localBeachDistributionEnvironment . ' already exists');
            $io->text('This command would create a new ' . basename($localBeachDistributionEnvironment) . ' file, please use --force to overwrite the existing file.');
        }

        if ($input->getOption('createDatabase')) {
            exec('docker exec local_beach_database /bin/bash -c "echo \'CREATE DATABASE IF NOT EXISTS \`' . $projectName . '\`\' | mysql -u root --password=password"');
        }

        $io->success('You are all set, now run beach local:start to get started.');

        return null;
    }
}
