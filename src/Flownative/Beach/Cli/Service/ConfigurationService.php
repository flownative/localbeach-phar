<?php
namespace Flownative\Beach\Cli\Service;

use Neos\Utility\Arrays;
use Symfony\Component\Yaml\Yaml;

final class ConfigurationService
{
    /**
     * @var array
     */
    private $settings;

    /**
     */
    public function __construct()
    {
        $this->settings = Yaml::parse(file_get_contents(CLI_ROOT_PATH .'config/settings.yaml'));
    }

    /**
     * Get a configuration value
     *
     * @param string $name
     * @throws \RuntimeException if the configuration is not defined.
     * @return null|string|bool|array
     */
    public function get($name)
    {
        return Arrays::getValueByPath($this->settings, $name);
    }
}
