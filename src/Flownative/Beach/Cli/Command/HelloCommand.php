<?php
namespace Flownative\Beach\Cli\Command;

use Flownative\Beach\Cli\Service\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloCommand extends CommandBase
{
    /**
     * @var Configuration
     */
    protected $cliConfig;

    /**
     * @required
     * @param Configuration $cliConfig
     */
    public function setConfig(Configuration $cliConfig)
    {
        $this->cliConfig = $cliConfig;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('hello')
            ->setDescription('Say hello');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello World');
        $output->writeln($this->cliConfig->get('application.name'));
    }
}
