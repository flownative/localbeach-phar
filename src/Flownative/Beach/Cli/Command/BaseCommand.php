<?php
namespace Flownative\Beach\Cli\Command;

use Flownative\Beach\Cli\Service\ConfigurationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var OutputInterface
     */
    protected $errorOutput;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->errorOutput = ($output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);
    }
}
