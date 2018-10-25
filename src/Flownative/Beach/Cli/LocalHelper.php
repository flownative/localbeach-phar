<?php
namespace Flownative\Beach\Cli;

use Dotenv\Dotenv;
use Neos\Utility\Files;

/**
 * Utility functions concerning Local Beach instances.
 */
abstract class LocalHelper
{
    /**
     * @param string $currentPath
     * @return string
     * @throws \Exception
     */
    public static function findFlowRootPathStartingFrom($currentPath)
    {
        $projectBasePath = Files::getNormalizedPath($currentPath);
        $dockerComposeYaml = $projectBasePath . '.localbeach.docker-compose.yaml';

        if (file_exists($dockerComposeYaml)) {
            return $projectBasePath;
        }

        if ($projectBasePath === '/') {
            throw new \Exception('Could not find Flow or Neos installation in your current path!', 1535642666);
        }

        return static::findFlowRootPathStartingFrom(dirname($currentPath));
    }

    /**
     * @param string $projectPath
     * @return string
     */
    public static function getLocalBeachFolderPath($projectPath)
    {
        return Files::getNormalizedPath($projectPath) . '.LocalBeach/';
    }

    /**
     * @param string $projectPath
     * @return string
     */
    public static function getLocalBeachDockerComposePathAndFilename($projectPath)
    {
        return Files::getNormalizedPath($projectPath) . '.localbeach.docker-compose.yaml';
    }

    /**
     * @param string $projectPath
     * @return string
     */
    public static function getLocalBeachDistributionEnvironmentFilePath($projectPath)
    {
        return Files::getNormalizedPath($projectPath) . '.localbeach.dist.env';
    }

    /**
     * @param string $projectPath
     * @return string
     */
    public static function getLocalBeachPersonalEnvironmentFilePath($projectPath)
    {
        return Files::getNormalizedPath($projectPath) . '.localbeach.env';
    }

    /**
     * @param string $projectPath
     */
    public static function loadLocalBeachEnvironment($projectPath)
    {
        $personalEnvironmentFilePath = static::getLocalBeachPersonalEnvironmentFilePath($projectPath);
        if (file_exists($personalEnvironmentFilePath)) {
            $personalEnvironmentLoader = new Dotenv(dirname($personalEnvironmentFilePath), basename($personalEnvironmentFilePath));
            $personalEnvironmentLoader->safeLoad();
        }

        $distributionEnvironmentFilePath = static::getLocalBeachDistributionEnvironmentFilePath($projectPath);
        if (file_exists($distributionEnvironmentFilePath)) {
            $distributionEnvironmentLoader = new Dotenv(dirname($distributionEnvironmentFilePath), basename($distributionEnvironmentFilePath));
            $distributionEnvironmentLoader->safeLoad();
        }
    }
}
