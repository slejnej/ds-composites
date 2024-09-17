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

  public function submitForm(array &$form, FormStateInterface $formState): void
  {
    $machineName = Drupal::config(ConfigureThemeForm::FORM_ID)->get('machine_name');
    Drupal::service('theme_installer')->install([$machineName]);
    Drupal::configFactory()->getEditable('system.theme')->set('default', $machineName)->save();
  }

  public function validateForm(array &$form, FormStateInterface $formState): bool
  {
    return true;
  }
}
