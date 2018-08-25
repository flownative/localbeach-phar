<?php
namespace Flownative\Beach\Cli\Command\Projects;

use Flownative\Beach\Cli\Command\BaseCommand;
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
            ->setName('project:init')
            ->setDescription('Initialize a Flow distribution as a Local Beach project')
            ->setHelp(
"The <info>project:init</info> command creates a Docker Compose configuration for a
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $projectBasePath = rtrim(getcwd(), '/') . '/';
        $flowPackagePath = $projectBasePath . 'Packages/Framework/Neos.Flow/';
        if (!file_exists($flowPackagePath)) {
            $io->error(sprintf('The path %s does not exist.', $flowPackagePath));
            $io->text('Please run this command from the root directory of your Flow or Neos installation.');
            return 1;
        }

        if (file_exists($projectBasePath . 'docker-compose.yml')) {
            if ($input->getOption('force')) {
                unlink($projectBasePath . 'docker-compose.yml');
            } else {
                $io->error('docker-compose.yml already exists');
                $io->text('This command will create a new docker-compose.yml, please use --force to overwrite the existing file.');
                return 1;
            }
        }

        copy(CLI_ROOT_PATH . 'resources/docker-compose.yml', $projectBasePath . 'docker-compose.yml');

        try {
            Files::createDirectoryRecursively($projectBasePath . 'Configuration/Development/Beach');
        } catch (FilesException $e) {
        }

        if (file_exists($projectBasePath . 'Configuration/Development/Beach/Settings.yaml')) {
            if ($input->getOption('force')) {
                unlink($projectBasePath . 'Configuration/Development/Beach/Settings.yaml');
            } else {
                $io->error($projectBasePath . 'Configuration/Development/Beach/Settings.yaml already exists');
                $io->text('This command will create a new Settings.yaml, please use --force to overwrite the existing file.');
                return 1;
            }
        }

        copy(CLI_ROOT_PATH . 'resources/Settings.yaml', $projectBasePath . 'Configuration/Development/Beach/Settings.yaml');

        if (file_exists($projectBasePath . '.env')) {
            if ($input->getOption('force')) {
                unlink($projectBasePath . '.env');
            } else {
                $io->error($projectBasePath . '.env already exists');
                $io->text('This command will create a new .env file, please use --force to overwrite the existing file.');
                return 1;
            }
        }

        $projectName = $input->getOption('projectName') ?: basename($projectBasePath);
        $projectName = preg_replace('/[^a-zA-Z0-9_]/', '', $projectName);

        file_put_contents($projectBasePath . '.env', str_replace('${BEACH_PROJECT_NAME}', $projectName, file_get_contents(CLI_ROOT_PATH . 'resources/.env')));

        if ($input->getOption('createDatabase')) {
            exec('docker exec local_beach_mariadb /bin/bash -c "echo \'CREATE DATABASE IF NOT EXISTS \`' . $projectName . '\`\' | mysql -u root --password=password"');
        }

        $io->success('You are all set, now run docker-compose up to get started.');

        return null;
    }
}
