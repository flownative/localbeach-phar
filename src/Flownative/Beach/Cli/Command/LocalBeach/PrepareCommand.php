<?php
namespace Flownative\Beach\Cli\Command\LocalBeach;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\Service\ConfigurationService;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Storage\StorageObject;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *
 */
class PrepareCommand extends BaseCommand
{

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('localbeach:prepare')
            ->addArgument('docker_folder', InputArgument::REQUIRED, 'Where to store docker metadata')
            ->addArgument('db_folder', InputArgument::REQUIRED, 'Where to store the database')
            ->setHidden(true);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws FilesException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $dockerFolder = $input->getArgument('docker_folder');
        $databaseFolder = $input->getArgument('db_folder');

        if (!is_dir($dockerFolder)) {
            Files::createDirectoryRecursively($dockerFolder);
        }

        if (!is_dir($databaseFolder)) {
            Files::createDirectoryRecursively($databaseFolder);
        }

        Files::copyDirectoryRecursively(CLI_ROOT_PATH . 'resources/local-beach', $dockerFolder);
        $dockerComposeFile = Files::getNormalizedPath($dockerFolder). 'docker-compose.yml';
        $dockerComposeContents = Files::getFileContents($dockerComposeFile);
        $dockerComposeContents = str_replace('{{databaseFolder}}', $databaseFolder, $dockerComposeContents);
        file_put_contents($dockerComposeFile, $dockerComposeContents);

        $io->success('Ready');
        return null;
    }
}
