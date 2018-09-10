<?php
namespace Flownative\Beach\Cli\Command\LocalBeach;

use Flownative\Beach\Cli\Command\BaseCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Start the local beach docker containers.
 */
class SetupCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('localbeach:setup');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $environment = getenv('BEACH_REMOTE_AUTHORIZED_KEYS') ?? null;
        if ($environment) {
            $io->success('Environment variable "BEACH_REMOTE_AUTHORIZED_KEYS" already set, doing nothing.');
            return 0;
        }

        $shell = $this->detectShell();
        $homeDirectory = $this->detectHomeDirectory();

        switch ($shell) {
            case 'zsh':
                $profilePathAndFilename = $homeDirectory . '/.zshrc';
            break;
            case 'bash':
                $profilePathAndFilename = $homeDirectory . '/.bashrc';
            break;
            default:
                $io->warning('Could not detect shell, please include the following to your shell environment:');
                $io->text($this->getEnvironmentLine());
                return 1;
        }


        $io->text('Installing environment variables in ' . $profilePathAndFilename);
        $this->writeEnvironment($profilePathAndFilename);
        $io->success('Please run "source ' . $profilePathAndFilename . '" or start a new shell.');
        return 0;
    }

    /**
     * @return string
     */
    protected function detectShell(): string
    {
        $shell = $_SERVER['SHELL'] ?? getenv('SHELL');
        if (!empty($shell)) {
            return basename($shell);
        }

        $homeDirectory = $this->detectHomeDirectory();

        if (empty($homeDirectory)) {
            return 'other';
        }

        if (file_exists($homeDirectory . '/.zshrc')) {
            return 'zsh';
        }
        if (file_exists($homeDirectory . '/.bashrc')) {
            return 'bash';
        }

        return 'other';
    }

    /**
     * @return string
     */
    protected function detectHomeDirectory(): string
    {
        $homeDirectory = $_SERVER['HOME'] ?? getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = exec("echo ~");
        }

        return $homeDirectory ?? '';
    }

    /**
     * @param string $filename
     */
    protected function writeEnvironment(string $filename)
    {
        if (!is_readable($filename)) {
            throw new RuntimeException("The file $filename was not found. Please create it manually and add the following line:\n" . $this->getEnvironmentLine(), 1536585747);
        }
        if (!is_writable($filename)) {
            throw new RuntimeException("The file $filename is not writable. Please add the following line manually:\n" . $this->getEnvironmentLine(), 1536585847);
        }

        $existingContent = file_get_contents($filename);
        if (strpos($existingContent, $this->getEnvironmentLine()) === false) {
            file_put_contents($filename, PHP_EOL . $this->getEnvironmentLine(), FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * @return string
     */
    protected function getEnvironmentLine(): string
    {
        return 'export BEACH_REMOTE_AUTHORIZED_KEYS=$(ssh-add -L | base64)';
    }
}
