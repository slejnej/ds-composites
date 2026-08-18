<?php

namespace Drupal\drupal_search\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Remora Search selected filters' Block.
 */
#[Block(
  id: "drupal_search_selected_filters_block",
  admin_label: new TranslatableMarkup("Drupal Search selected filters Block"),
  category: new TranslatableMarkup("Custom")
)]
class DrupalSearchSelectedFiltersBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    return [
      '#theme' => 'drupal_search_selected_filters_block',
    ];
  }
}
