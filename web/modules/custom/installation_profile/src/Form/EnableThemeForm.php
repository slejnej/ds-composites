<?php

namespace Drupal\installation_profile\Form;

use Drupal;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

class EnableThemeForm implements FormInterface
{

  public const FORM_ID = 'installation_profile.theme_enable';

  public function getFormId(): string
  {
    return self::FORM_ID;
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = Drupal::config(ConfigureThemeForm::FORM_ID);

    if ($config->get('skip_theme')) {
      // Get all enabled themes.
      $themeHandler = Drupal::service('theme_handler');
      $enabledThemes = $themeHandler->rebuildThemeData();

      $themeOptions = [];
      foreach ($enabledThemes as $machineName => $themeInfo) {
        $themeOptions[$machineName] = $themeInfo->info['name'] ?? $machineName;
      }

      $form['default_theme'] = [
        '#type' => 'select',
        '#title' => t('Select default theme'),
        '#options' => $themeOptions,
        '#default_value' => Drupal::config('system.theme')->get('default'),
        '#description' => t('Choose any available theme to set as the default. If it is not currently enabled, it will be enabled automatically.'),
        '#required' => true,
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Continue with selected theme'),
        '#weight' => 15,
        '#button_type' => 'primary',
      ];

      $form['#title'] = t('You have decided to skip Theme creation');
      return $form;
    }

    return [
      '#title' => 'Enable subtheme',
      '#description' => 'This form is needed so Drupal actually recognizes the subtheme...',
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => 'Yep enable',
          '#weight' => 15,
          '#button_type' => 'primary',
        ]
      ]
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $config = Drupal::config(ConfigureThemeForm::FORM_ID);

    if ($config->get('skip_theme')) {
      $selectedTheme = $form_state->getValue('default_theme');

      if ($selectedTheme) {
        $themeHandler = Drupal::service('theme_handler');
        $enabledThemes = $themeHandler->listInfo();

        // Enable the theme if it's not already enabled.
        if (!isset($enabledThemes[$selectedTheme])) {
          $themeInstaller = Drupal::service('theme_installer');
          $themeInstaller->install([$selectedTheme]);
        }

        // Set as default.
        Drupal::configFactory()->getEditable('system.theme')->set('default', $selectedTheme)->save();
        Drupal::messenger()->addMessage(t('Theme %theme has been enabled and set as default.', ['%theme' => $selectedTheme]));
      }
      else {
        Drupal::messenger()->addError(t('No theme was selected.'));
      }

      return;
    }

    $machineName = $config->get('machine_name');
    Drupal::service('theme_installer')->install([$machineName]);
    Drupal::configFactory()->getEditable('system.theme')->set('default', $machineName)->save();
  }

  public function validateForm(array &$form, FormStateInterface $form_state): bool
  {
    return true;
  }
}
