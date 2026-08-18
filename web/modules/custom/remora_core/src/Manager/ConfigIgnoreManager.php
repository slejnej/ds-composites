<?php

namespace Drupal\remora_core\Manager;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\user\Entity\Role;
use Psr\Log\LoggerInterface;

class ConfigIgnoreManager
{

  public function __construct(private readonly FileSystemInterface $fileSystem, private readonly LoggerInterface $logger)
  {
  }

  /**
   * Reads the config_ignore.yml file for the module and adds the ignore terms to the config ignore
   *
   * @param string $moduleName The module's machinename to handle config ignore for
   * @return bool True if the config ignore file exists, false otherwise
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function addToConfigIgnore(string $moduleName): bool
  {
    $ignoreFile = $this->fileSystem->realpath("$moduleName://config/remora/config_ignore.yml");
    if(!$ignoreFile) {
      return false;
    }

    $this->logger->info('Syncing config ignore for module ' . $moduleName);

    // parse the YAML file
    $ignoreConfig = Yaml::decode(file_get_contents($ignoreFile));

    $ignoreTerms = $ignoreConfig['ignore'];

    // Get the active configuration for config_ignore.settings.
    $config = Drupal::configFactory()->getEditable('config_ignore.settings');

    // Get the current ignored entities array.
    $ignoredEntities = $config->get('ignored_config_entities', []);

    // grant all ignore terms to the config ignore
    foreach($ignoreTerms as $ignoreTerm) {

      if (!in_array($ignoreTerm, $ignoredEntities)) {
        $ignoredEntities[] = $ignoreTerm;
      }
    }

    // Save the updated configuration.
    $config->set('ignored_config_entities', $ignoredEntities)->save();

    $this->logger->info("Added terms to config ignore");

    return true;
  }
}
