<?php

namespace Drupal\security\Form;

use Drupal\remora_core\Middleware\RemoveHttpHeadersMiddleware;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows you to define the HTTP headers and metatags to remove from every request
 *
 *
 * @see RemoveHttpHeadersMiddleware
 *
 */
class ObfuscateVersionsForm extends ConfigFormBase
{

  public const FORM_ID = 'security.obfuscate_versions.settings';
  public const CONFIG_ID = 'security.obfuscate_versions.settings';
  private const DEFAULT_HEADERS = ['x-powered-by', 'server', 'x-generator', 'x-drupal-cache', 'x-drupal-dynamic-cache', 'server', 'x-drupal-cache-contexts', 'x-drupal-cache-max-age', 'x-drupal-cache-tags'];
  private const DEFAULT_METATAGS = ['Generator'];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return self::FORM_ID;
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(self::CONFIG_ID);
    $form_state->setCached(FALSE);

    $form = [
      'details' => [
        '#type' => 'fieldset',
        '#title' => $this->t('HTTP Headers and Metatags to remove'),
        '#description' => $this->t('<b>Don\'t forget to clear the cache after changing these settings!</b> For some reason Drupal doesn\'t always clear the config cache...'),
        '#description_display' => 'before',
        'remove_http_headers' => [
          '#type' => 'textarea',
          '#title' => $this->t('Remove headers list'),
          '#description' => $this->t('List of HTTP headers to be removed (one per line).'),
          '#default_value' => $config->get('remove_http_headers') ?? implode("\n", self::DEFAULT_HEADERS),
          '#required' => FALSE
        ],
        'remove_metatags' => [
          '#type' => 'textarea',
          '#title' => $this->t('Remove metatags list'),
          '#description' => $this->t('List of metatags to be removed (one per line).'),
          '#default_value' => $config->get('remove_metatags') ?? implode("\n", self::DEFAULT_METATAGS),
          '#required' => FALSE
        ]
        ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    parent::submitForm($form, $form_state);

    $config = $this->config(self::CONFIG_ID);


    foreach (['remove_http_headers', 'remove_metatags'] as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array
  {
    return [self::CONFIG_ID];
  }
}
