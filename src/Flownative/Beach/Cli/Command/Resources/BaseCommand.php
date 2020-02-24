<?php
namespace Flownative\Beach\Cli\Command\Resources;

use Flownative\Beach\Cli\Command\BaseCommand as BaseBaseCommand;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Storage\Bucket;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BaseCommand extends BaseBaseCommand
{
    /**
     * @param string $instanceIdentifier
     * @param SymfonyStyle $io
     * @return Bucket
     */
    protected function determineStorageBucketForInstance(string $instanceIdentifier, SymfonyStyle $io): ?Bucket
    {
        if (preg_match('/^instance\-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $instanceIdentifier) === 0) {
            $io->error(sprintf('The instance identifier "%s" is not valid.', $instanceIdentifier));
            return null;
        }

        $io->text('Retrieving cloud storage access data from instance ...');
        $io->newLine();

        $environmentVariables = [];
        exec('ssh -J beach@ssh.flownative.cloud beach@' . $instanceIdentifier . '.beach /bin/bash -c "env | grep BEACH_GOOGLE_CLOUD_STORAGE_"', $environmentLines);

        foreach ($environmentLines as $line) {
            [$key, $value] = explode('=', $line);
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
            return null;
        }

        $privateKey = json_decode(base64_decode($environmentVariables['BEACH_GOOGLE_CLOUD_STORAGE_SERVICE_ACCOUNT_PRIVATE_KEY']), true);

        $googleCloud = new ServiceBuilder([
            'keyFile' => $privateKey
        ]);

        $googleCloudStorage = $googleCloud->storage();
        return $googleCloudStorage->bucket($bucketName);
    }
}
