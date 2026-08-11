<?php

namespace Drupal\installation_profile\Form;

use Drupal;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\installation_profile\Exception\PathExistsException;
use Drupal\installation_profile\Manager\FigmaSassManager;
use Drupal\installation_profile\Manager\ThemeManager;
use Drupal\installation_profile\Util\StringUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfigureThemeForm implements FormInterface
{

  public const FORM_ID = 'installation_profile.theme_config';

  public function getFormId(): string
  {
    return self::FORM_ID;
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    return [
      '#title' => 'Configure Remora theme',
      '#description' => 'Configure all the color values for the main Remora SCSS variables',

      'skip_theme_container' => [
        '#type' => 'fieldset',
        '#title' => t('Skip Theme Creation'),
        '#description' => t('Check this if you do not want to generate and enable a custom subtheme.'),
        '#weight' => -30, // ensure it appears above the rest

        'skip_theme' => [
          '#type' => 'checkbox',
          '#title' => t('Skip theme creation'),
          '#default_value' => FALSE,
        ],
      ],

      'theme_name' => [
        '#type' => 'textfield',
        '#title' => t('Theme name'),
        '#description' => t('The theme\'s human readable name. The <code>_theme</code> suffix will <em>NOT</em> automatically be added to the theme\'s machine name.'),
        '#required' => FALSE,
        '#weight' => -20,
      ],

      'figma_files' => [
        '#type' => 'file',
        '#title' => t('Figma variables'),
        '#multiple' => TRUE,
        '#upload_location' => 'tmp://',
        '#upload_validators' => [
          'file_validate_extensions' => ['json'],
        ],
      ],

      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => t('Save and continue'),
          '#weight' => 15,
          '#button_type' => 'primary',
        ],
      ],
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $skip = $form_state->getValue('skip_theme');
    if ($skip) {
      Drupal::configFactory()->getEditable(self::FORM_ID)
        ->set('skip_theme', $skip)
        ->save();

    } else {
      $name = $form_state->getValue('theme_name');
      $machineName = StringUtil::sanitizeThemeName($name);
      $messenger = Drupal::messenger();
      // save the machine name so we can enable it in the next step
      Drupal::configFactory()
        ->getEditable(self::FORM_ID)
        ->set('machine_name', $machineName)
        ->save();

      /** @var UploadedFile[] $figmaFiles */
      $figmaFiles = $form_state->getValue('figma_files');
      $scss = FigmaSassManager::fromUploadedFile($figmaFiles)->toScss();

      try {
        $success = (new ThemeManager($machineName, $name))
          ->init()
          ->addScssFiles($scss)
          ->write();
      }
      catch (PathExistsException) {
        $success = FALSE;
      }

      if ($success) {
        $messenger->addMessage("Generated subtheme $name ($machineName).");
      }
      else {
        $messenger->addError("Something went wrong generating subtheme $name ($machineName).");
      }
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state): bool
  {
    return true;
  }
}
