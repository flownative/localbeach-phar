<?php
namespace Flownative\Beach\Cli\Command\Resources;

use Flownative\Beach\Cli\Command\BaseCommand;
use Flownative\Beach\Cli\Service\ConfigurationService;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Storage\StorageObject;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends BaseCommand
{
    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @required
     * @param ConfigurationService $cliConfig
     */
    public function setConfig(ConfigurationService $cliConfig)
    {
        $this->configurationService = $cliConfig;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('resource:download')
            ->setDescription('Preliminary solution for downloading resources (assets) from Beach to a local Flow or Neos')
            ->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, 'Name of the Google Cloud Storage bucket')
            ->addOption('clientEmail', 'c', InputOption::VALUE_REQUIRED, 'Client email address of the Google Cloud Storage service account')
            ->addOption('privateKey', 'p', InputOption::VALUE_REQUIRED, 'Base64-encoded private key of the Google Cloud Storage service account')
            ->addOption('localFlowRootPath', 'l', InputOption::VALUE_REQUIRED, 'Path leading to the local Flow or Neos root path');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws FilesException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $localFlowRootPath = $input->getOption('localFlowRootPath');
        if (!file_exists($localFlowRootPath)) {
            $this->errorOutput->writeln('The given local Flow root path does not exist.');
            return 1;
        }
        $localPersistentPath = rtrim($localFlowRootPath, '/') . '/Data/Persistent/';
        if (!file_exists($localPersistentPath)) {
            $this->errorOutput->writeln(sprintf('The path %s does not exist.', $localPersistentPath));
            return 2;
        }
        $localResourcesPath = $localPersistentPath . 'Resources/';
        if (!file_exists($localResourcesPath)) {
            mkdir($localResourcesPath);
        }

        $bucketName = $input->getOption('bucket');
        $privateKey = json_decode(base64_decode($input->getOption('privateKey')), true);

        $googleCloud = new ServiceBuilder([
            'keyFile' => $privateKey
        ]);

        $googleCloudStorage = $googleCloud->storage();
        $bucket = $googleCloudStorage->bucket($bucketName);


        $output->writeln(sprintf("Downloading resources from bucket\n   %s\nto directory\n   %s ...", $bucketName, $localResourcesPath));

        foreach ($bucket->objects() as $storageObject) {
            /** @var StorageObject $storageObject */
            $targetPathAndFilename = $localResourcesPath . $this->getRelativePathAndFilenameByHash($storageObject->name());
            if (!file_exists(dirname($targetPathAndFilename))) {
                Files::createDirectoryRecursively(dirname($targetPathAndFilename));
            }
            if (!file_exists($targetPathAndFilename)) {
                $storageObject->downloadToFile($targetPathAndFilename);
            }
        }

        $output->writeln('Done.');
        return null;
    }

    /**
     * Determines and returns the absolute path and filename for a storage file identified by the given SHA1 hash.
     *
     * @param string $sha1Hash The SHA1 hash identifying the stored resource
     * @return string The path and filename, for example "c/8/2/8/c828d0f88ce197be1aff7cc2e5e86b1244241ac6"
     */
    protected function getRelativePathAndFilenameByHash(string $sha1Hash): string
    {
        return $sha1Hash[0] . '/' . $sha1Hash[1] . '/' . $sha1Hash[2] . '/' . $sha1Hash[3] . '/' . $sha1Hash;
    }
}
