<?php

namespace Drupal\remora_core\Service;

use Drupal;
use Drupal\Component\Serialization\Yaml;

/**
 * Used to install dependencies from a module.
 * Takes the dependencies list from the info.yml file of a given module.
 * Loops through that list and installs any modules that are not already installed.
 */
class InstallDependenciesService
{

  /**
   * @param string $moduleName
   * @return void
   */
  public function installModuleDependencies(string $moduleName): void
  {
    // Get the path to the info file.
    $infoFilePath = Drupal::service('file_system')->realPath(sprintf('%1$s://%1$s.info.yml', $moduleName));

    // Read the info file to get the module dependencies.
    $info = Yaml::decode(file_get_contents($infoFilePath));
    $dependencies = $info['dependencies'] ?? [];

    foreach ($dependencies as $dependency) {
      // Extract the module name from the dependency string.
      $dependentModuleName = explode(':', $dependency)[1];

      // Check if the module is not already installed.
      if (!Drupal::moduleHandler()->moduleExists($dependentModuleName)) {
        // Install the module.
        Drupal::service('module_installer')->install([$dependentModuleName]);
        Drupal::messenger()->addMessage(t('Installed module: @module', ['@module' => $dependentModuleName]));
      }
    }

  }

}