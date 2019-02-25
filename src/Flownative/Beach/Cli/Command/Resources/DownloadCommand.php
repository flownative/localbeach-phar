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
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->setDescription('Download resources (assets) from Beach to a local Flow or Neos installation')
            ->setHelp(
"The <info>resource:download</info> command downloads Flow resources from a Beach instance to
a local Flow or Neos project.

Resource data (that is, the actual files containing binary data) will be downloaded to the
<info>Data/Persistent/Resources</info> directory.

It is your responsibility to make sure that the database content is matching this data. Be
aware that Neos and Flow keep track of existing resources by a database table.
If resources are not registered in there, Flow does not know about them.

Use this command by switching to the root directory of your Flow or Neos installation and
then running <info>resource:download</info> and specify the instance identifier.

Note: Existing data in the local project instance will be left unchanged.
"
            )
            ->addOption('instance', 'i', InputOption::VALUE_REQUIRED, 'Instance identifier, e.g. "instance-123abcde-456-abcd-1234-abcdef012345"');
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

        $localPersistentPath = rtrim(getcwd(), '/') . '/Data/Persistent/';
        if (!file_exists($localPersistentPath)) {
            $io->error(sprintf('The path %s does not exist.', $localPersistentPath));
            $io->text('Please run this command from the root directory of your Flow or Neos installation.');
            return 1;
        }
        $localResourcesPath = $localPersistentPath . 'Resources/';
        if (!file_exists($localResourcesPath)) {
            mkdir($localResourcesPath);
        }

        $instanceIdentifier = $input->getOption('instance');
        if (preg_match('/^instance\-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $instanceIdentifier) === 0) {
            $io->error(sprintf('The instance identifier is not valid.', $instanceIdentifier));
            return 1;
        }

        $io->text('Retrieving cloud storage access data from instance ...');
        $io->newLine();

        $environmentVariables = [];
        exec('ssh -J beach@ssh.flownative.cloud beach@' . $instanceIdentifier . ' /bin/bash -c "env | grep BEACH_GOOGLE_CLOUD_STORAGE_"', $environmentLines);

        foreach ($environmentLines as $line) {
            list($key, $value) = explode("=", $line);
            $environmentVariables[$key] = $value;
        }

        $bucketName = '';
        if (isset($environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_STORAGE_BUCKET'])) {
            $bucketName = $environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_STORAGE_BUCKET'];
        } elseif (isset($environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_PUBLIC_BUCKET'])) {
            $bucketName = $environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_PUBLIC_BUCKET'];
        }

        if (empty($bucketName)) {
            $io->error('Could not retrieve cloud storage information from instance.');
            return 1;
        }

        $privateKey = json_decode(base64_decode($environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_SERVICE_ACCOUNT_PRIVATE_KEY']), true);

        $googleCloud = new ServiceBuilder([
            'keyFile' => $privateKey
        ]);

        $googleCloudStorage = $googleCloud->storage();
        $bucket = $googleCloudStorage->bucket($bucketName);

        $io->text([
            "Downloading resources from bucket",
            "<info>$bucketName</info>",
            "to local directory",
            "<info>$localResourcesPath</info>"
        ]);
        $io->newLine();

        $io->progressStart();
        foreach ($bucket->objects() as $storageObject) {
            $io->progressAdvance();
            /** @var StorageObject $storageObject */
            $targetPathAndFilename = $localResourcesPath . $this->getRelativePathAndFilenameByHash($storageObject->name());
            if (!file_exists(dirname($targetPathAndFilename))) {
                Files::createDirectoryRecursively(dirname($targetPathAndFilename));
            }
            if (!file_exists($targetPathAndFilename)) {
                $storageObject->downloadToFile($targetPathAndFilename);
            }
        }
        $io->progressFinish();

        $io->success('Done.');
        return null;
    }

    /**
     * Determines and returns the absolute path and filename for a storage file identified by the given SHA1 hash.
     *
     * @param string $sha1Hash The SHA1 hash identifying the stored resource
     * @return string The path and filename, for example "c/8/2/8/c828d0f88ce197be1aff7cc2e5e86b1244241ac6"
     */
    private function getRelativePathAndFilenameByHash(string $sha1Hash): string
    {
        return $sha1Hash[0] . '/' . $sha1Hash[1] . '/' . $sha1Hash[2] . '/' . $sha1Hash[3] . '/' . $sha1Hash;
    }
}
