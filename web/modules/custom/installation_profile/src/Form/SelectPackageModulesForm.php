<?php

namespace Drupal\installation_profile\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

class SelectPackageModulesForm implements FormInterface
{
  public const PACKAGE_NAME = 'Dc2';
  private const SKIP_MODULES = ['remora_core'];
  private const GROUP_MODULES = ['taxonomy', 'pod', 'content type'];

  public function getFormId()
  {
    return 'your_profile_select_package_modules';
  }

  /**
   * Builds a form to select modules from a specific package.
   *
   * @param array $form
   *   An associative array containing the structure and elements of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array representing the form elements.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $packageName = static::PACKAGE_NAME;
    $extensionList = \Drupal::service('extension.list.module')->getList();

    $enabled_modules = array_filter(\Drupal::service('extension.list.module')->getList(), function($module) {
      return \Drupal::moduleHandler()->moduleExists($module->getName());
    });

    $enabled_modules_list = array_keys($enabled_modules);

    $grouped_modules = [];
    $default_group = 'Other';

    foreach ($extensionList as $name => $extension) {
      $info = $extension->info;

      if (empty($info['package']) || $info['package'] !== $packageName ||
        in_array($name, static::SKIP_MODULES) || in_array($name, $enabled_modules_list)) {
        continue;
      }

      $group = $default_group;
      foreach (static::GROUP_MODULES as $keyword) {
        if (stripos($name, str_replace(' ', '_', $keyword)) !== FALSE || stripos($info['name'] ?? '', $keyword) !== FALSE) {
          $group = ucfirst($keyword);
          break;
        }
      }

      $grouped_modules[$group][$name] = $info['name'] ?? $name;
    }

    $form['#title'] = t('Select modules from package: @package', ['@package' => $packageName]);
    $form['modules'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    foreach ($grouped_modules as $group => $modules) {
      $safe_group_id = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $group));

      $form['modules'][$safe_group_id] = [
        '#type' => 'fieldset',
        '#title' => $group,
        '#attributes' => ['class' => ['module-group'], 'data-group' => $safe_group_id],
      ];

      // Select all
      $form['modules'][$safe_group_id]['select_all'] = [
        '#type' => 'checkbox',
        '#title' => t('Select all'),
        '#attributes' => [
          'class' => ['select-all', 'bold-label'],
          'data-group' => $safe_group_id,
        ],
      ];

      // checkboxes
      foreach ($modules as $machine_name => $label) {
        $form['modules'][$safe_group_id][$machine_name] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => FALSE,
          '#attributes' => ['class' => ['module-checkbox'], 'data-group' => $safe_group_id],
        ];
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue'),
      '#button_type' => 'primary',
    ];

    // Attach inline javascript
    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => <<<JS
        document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('.select-all').forEach(function(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
              const group = this.getAttribute('data-group');
              const checkboxes = document.querySelectorAll(
                '[data-group="' + group + '"].module-checkbox'
              );
              checkboxes.forEach(function(cb) {
                cb.checked = selectAllCheckbox.checked;
              });
            });
          });
        });
      JS,
      ],
      'select_all_script',
    ];

    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '.bold-label + label { font-weight: bold!important; }',
      ],
      'select_all_css',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    // No special validation here.
  }

  /**
   * Submits the form and sets the selected package modules in the state.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $values = $form_state->getValue('modules');
    $selected = [];

    foreach ($values as $group => $checkboxes) {
      foreach ($checkboxes as $module => $enabled) {
        if ($module === 'select_all') {
          continue;
        }

        if ($enabled) {
          $selected[] = $module;
        }
      }
    }

    \Drupal::state()->set('installation_profile.selected_package_modules', $selected);
  }

}
