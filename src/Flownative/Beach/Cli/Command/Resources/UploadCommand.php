<?php
namespace Flownative\Beach\Cli\Command\Resources;

use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UploadCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('resource:upload')
            ->setDescription('Upload resources (assets) from a local Flow or Neos installation to Beach')
            ->setHelp(
'The <info>resource:upload</info> command uploads Flow resources from a local Flow or Neos
project to a Beach instance.

Resource data (that is, the actual files containing binary data) will be uploaded from the
<info>Data/Persistent/Resources</info> directory.

It is your responsibility to make sure that the database content is matching this data. Be
aware that Neos and Flow keep track of existing resources by a database table.
If resources are not registered in there, Flow does not know about them.

Use this command by switching to the root directory of your Flow or Neos installation and
then running <info>resource:upload</info> and specify the instance identifier.

Note: Existing data in the Beach instance will be left unchanged.
'
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
            $io->error(sprintf('The path %s does not exist.', $localResourcesPath));
            $io->text('Please run this command from the root directory of your Flow or Neos installation.');
            return 1;
        }

        $bucket = $this->determineStorageBucketForInstance($input->getOption('instance'), $io);
        if ($bucket === null) {
            return 1;
        }

        $io->text([
            'Uploading resources from local directory',
            "<info>$localResourcesPath</info>",
            'to bucket',
            '<info>' . $bucket->name() . '</info>'
        ]);
        $io->newLine();

        $io->progressStart();
        foreach (Files::getRecursiveDirectoryGenerator($localResourcesPath) as $sourcePathAndFilename) {
            $io->progressAdvance();
            if (preg_match('/[a-f0-9]{40}$/', $sourcePathAndFilename) !== 1) {
                continue;
            }
            $bucket->upload(fopen($sourcePathAndFilename, 'rb'), [
                'name' => basename($sourcePathAndFilename)
            ]);
        }
        $io->progressFinish();

        $io->success('Done.');
        return null;
    }
}
