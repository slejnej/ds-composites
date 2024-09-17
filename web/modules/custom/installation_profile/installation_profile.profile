<?php

use Drupal\installation_profile\Form\ConfigureThemeForm;
use Drupal\installation_profile\Form\EnableThemeForm;
use Drupal\installation_profile\Manager\ModuleScssManager;

function installation_profile_install_tasks(): array {
  return [
    'configure_theme' => [
      'display_name' => 'Configure theme',
      'display' => true,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => ConfigureThemeForm::class
    ],
    // install and enable the theme
    // this has to be done in a new step since Drupal doesn't recognise the theme until now...
    'enable_theme' => [
      'display_name' => 'Enabling theme',
      'display' => true,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => EnableThemeForm::class
    ],
    'clean_up' => [
      'display_name' => 'Cleaning up',
      'display' => true,
      'type' => 'function',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => function() {
        // delete the config so it's not exported
        Drupal::configFactory()->getEditable(ConfigureThemeForm::FORM_ID)->delete();
      }
    ]
  ];
}

/**
 * Implements hook_modules_installed
 * Loops over all the modules and links the SCSS file to the subtheme
 *
 * @param array $modules An array of module names being installed
 * @param bool $isSyncing Whether the module is being installed through config:import or not
 * @return void
 * @see ModuleScssManager::linkStyles()
 */
function installation_profile_modules_installed(array $modules, bool $isSyncing): void
{
  // if the module is being installed from a config sync, do nothing. aka only run on pm:e
  if($isSyncing) {
    return;
  }
  /** @var ModuleScssManager $moduleScssManager */
  $moduleScssManager = Drupal::service('installation_profile.manager.module_scss');
  $logger = Drupal::service('installation_profile.logger');

  foreach($modules as $module) {
    $logger->info("Linking styles for module $module");

    try {
      $moduleScssManager->linkStyles($module);
    } catch(Exception $e) {
      $logger->error($e->getMessage());
    }
  }
}

/**
 * Implements hook_modules_installed
 * Loops over all the modules and removes the import from the subtheme's _module file where applicable
 *
 * @param array $modules An array of module names being installed
 * @param bool $isSyncing Whether the module is being installed through config:import or not
 * @return void
 * @see ModuleScssManager::unlinkStyles()
 */
function installation_profile_modules_uninstalled(array $modules, bool $isSyncing): void
{
  // if the module is being uninstalled from a config sync, do nothing. aka only run on pm:u
  if($isSyncing) {
    return;
  }

  /** @var ModuleScssManager $moduleScssManager */
  $moduleScssManager = Drupal::service('installation_profile.manager.module_scss');
  $logger = Drupal::service('installation_profile.logger');

  foreach($modules as $module) {
    $logger->info("Removing styles for module $module");

    try {
      $moduleScssManager->unlinkStyles($module);
    } catch(Exception $e) {
      $logger->error($e->getMessage());
    }
  }
}
