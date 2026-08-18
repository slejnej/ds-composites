<?php

namespace Drupal\drupal_search\Plugin\Block;

use Drupal;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Block(
  id: "drupal_search_nav_block",
  admin_label: new TranslatableMarkup("Drupal Search expanding header Block"),
  category: new TranslatableMarkup("Custom")
)]
class DrupalSearchHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    return [
      '#theme' => 'drupal_search_header_block',
      '#language' => Drupal::languageManager()->getCurrentLanguage()->getId(),
      '#attached' => [
        'library' => [
          'drupal_search/drupal_search_header_block',
        ],
      ],
      '#cache' => ['contexts' => ['languages:language_interface']],
    ];
  }
}
