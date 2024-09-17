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
      'theme_name' => [
        '#type' => 'textfield',
        '#title' => 'Theme name',
        '#description' => 'The theme\'s human readable name. The <code>_theme</code> suffix will <em>NOT</em> automatically be added to the theme\'s machine name.',
        '#required' => TRUE,
        '#weight' => -20,
      ],
      'figma_files' => [
        '#type' => 'file',
        '#title' => t('Figma variables'),
        '#multiple' => true,
        '#upload_location' => 'tmp://',
        '#upload_validators' => [
          'file_validate_extensions' => ['json']
        ]
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => 'Save and continue',
          '#weight' => 15,
          '#button_type' => 'primary',
        ]
      ]
    ];
  }

  public function submitForm(array &$form, FormStateInterface $formState): void
  {
    $name = $formState->getValue('theme_name');
    $machineName = StringUtil::sanitizeThemeName($name);
    $messenger = Drupal::messenger();
    // save the machine name so we can enable it in the next step
    Drupal::configFactory()->getEditable(self::FORM_ID)->set('machine_name', $machineName)->save();

    /** @var UploadedFile[] $figmaFiles */
    $figmaFiles = $formState->getValue('figma_files');
    $scss = FigmaSassManager::fromUploadedFile($figmaFiles)->toScss();

    try {
      $success = (new ThemeManager($machineName, $name))
        ->init()
        ->addScssFiles($scss)
        ->write()
      ;
    } catch(PathExistsException) {
      $success = false;
    }

    if($success) {
      $messenger->addMessage("Generated subtheme $name ($machineName).");
    } else {
      $messenger->addError("Something went wrong generating subtheme $name ($machineName).");
    }
  }

  public function validateForm(array &$form, FormStateInterface $formState): bool
  {
    return true;
  }
}
