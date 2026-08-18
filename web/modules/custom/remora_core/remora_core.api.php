<?php

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Alter the human-readable paragraph name.
 *
 * @param string $human_readable_name
 *   The paragraph label, initially set to the paragraph type label.
 * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
 *   The paragraph entity being rendered.
 * @param array $variables
 *   The preprocess variables for the paragraph template.
 */
function hook_remora_core_human_readable_name_alter(string &$human_readable_name, Paragraph $paragraph, array &$variables): void
{
  // Example:
  // $human_readable_name .= ' (extra details)';
}
