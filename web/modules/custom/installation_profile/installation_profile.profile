<?php

use Drupal\installation_profile\Form\ConfigureThemeForm;
use Drupal\installation_profile\Form\EnableThemeForm;
use Drupal\installation_profile\Form\SelectPackageModulesForm;
use Drupal\installation_profile\Manager\ModuleScssManager;
use Drupal\Core\State\StateInterface;

function installation_profile_install_tasks(): array {
  return [
    'configure_theme' => [
      'display_name' => 'Configure theme',
      'display' => true,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => ConfigureThemeForm::class,
    ],
    'enable_theme' => [
      'display_name' => 'Enabling theme',
      'display' => true,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => EnableThemeForm::class,
    ],
    'clean_up' => [
      'display_name' => 'Cleaning up',
      'display' => true,
      'type' => 'function',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => function () {
        \Drupal::configFactory()->getEditable(ConfigureThemeForm::FORM_ID)->delete();
      }
    ],
    'install_modules' => [
      'display_name' => 'Package modules install',
      'display' => true,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => SelectPackageModulesForm::class,
    ],
    'install_selected_modules' => [
      'display_name' => 'Installing selected modules',
      'display' => TRUE,
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => 'installation_profile_run_selected_modules_batch',
    ],
  ];
}

/**
 * Implements hook_modules_installed
 * Loops over all the modules and links the SCSS file to the subtheme
 *
 * @param array $modules An array of module names being installed
 * @param bool $isSyncing Whether the module is being installed through config:import or not
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
  // Check if the service exists before using it
  if (\Drupal::hasService('installation_profile.manager.module_scss')) {
    /** @var ModuleScssManager $moduleScssManager */
    $moduleScssManager = \Drupal::service('installation_profile.manager.module_scss');
    // Use the service
  }

  if (\Drupal::hasService('installation_profile.logger')) {
    $logger = \Drupal::service('installation_profile.logger');
    // Use the logger
  }

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

/**
 * Batch runner for installing selected modules.
 */
function installation_profile_run_selected_modules_batch(&$install_state) {
  $selected = \Drupal::state()->get('installation_profile.selected_package_modules', []);
  \Drupal::state()->delete('installation_profile.selected_package_modules');

  if (empty($selected)) {
    return [];
  }

  $operations = [];
  foreach ($selected as $module) {
    $operations[] = ['installation_profile_install_module_batch_op', [$module]];
  }

  return [
    'title' => t('Installing selected modules...'),
    'progress_message' => t('Installing @current out of @total modules.'),
    'operations' => $operations,
    'finished' => 'installation_profile_selected_modules_batch_finished',
  ];
}


/**
 * Batch operation.
 */
function installation_profile_install_module_batch_op($module, &$context) {
  try {
    \Drupal::service('module_installer')->install([$module], TRUE);
    $context['results']['installed'][] = $module;
  } catch (\Throwable $e) {
    $context['results']['errors'][] = "Error installing {$module}: " . $e->getMessage();
  }
}

/**
 * Batch finished callback.
 */
function installation_profile_selected_modules_batch_finished($success, $results, $operations) {
  if (!empty($results['installed'])) {
    \Drupal::logger('installation_profile')->notice('Installed modules: @modules', [
      '@modules' => implode(', ', $results['installed']),
    ]);
  }

  if (!empty($results['errors'])) {
    \Drupal::logger('installation_profile')->error(implode("\n", $results['errors']));
  }
}