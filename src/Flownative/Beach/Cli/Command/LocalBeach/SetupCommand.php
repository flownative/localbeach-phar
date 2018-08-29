<?php
namespace Flownative\Beach\Cli\Command\LocalBeach;

use Flownative\Beach\Cli\Command\BaseCommand;
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
            $io->success('Environment variable already set, doing nothing.');
            return 0;
        }

        $shell = $this->detectShell();
        $homeDirectory = $this->detectHomeDirectory();

        if ($shell === 'zsh') {
            $io->text('Detected ZSH. Installing environment');
            $this->writeEnvironment($homeDirectory . '/.zshrc');
            $io->success('Done');
            return 0;
        }

        if ($shell === 'bash') {
            $io->text('Detected BASH. Installing environment');
            $this->writeEnvironment($homeDirectory . '/.bashrc');
            $io->success('Done');
            return 0;
        }

        $io->warning('Could not detect shell, please include the following to your shell environment:');
        $io->text($this->getEnvironmentLine());

        return 1;
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
        file_put_contents($filename, PHP_EOL . $this->getEnvironmentLine(), FILE_APPEND | LOCK_EX);
    }

    /**
     * @return string
     */
    protected function getEnvironmentLine(): string
    {
        return 'export BEACH_REMOTE_AUTHORIZED_KEYS=$(ssh-add -L | base64)';
    }
}
