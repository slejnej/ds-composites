<?php

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;

/**
 * Adds the remora token type and the tokens seo_title and seo_description
 */
function remora_core_token_info(): array
{
  return [
    'types' => [
      'remora' =>  [
        'name' => 'Remora variables',
        'description' => t('Tokens provided by Remora.')
      ]
    ],
    'tokens' => [
      'remora' => [
        'seo_title' => [
          'name' => t('SEO Title'),
          'description' => t('Automatically uses the alternative title if available. Otherwise uses the node title.'),
        ],
        'seo_description' => [
          'name' => t('SEO Description'),
          'description' => t('Automatically displays the summary field if available.'),
        ],
      ]
    ]
  ];
}

/**
 * Implementation for seo_title and seo_description
 */
function remora_core_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array
{
  // get the node from the request
  $attributes = Drupal::request()->attributes;
  $node = $attributes->get('node') ?? $attributes->get('node_preview');

  if(!isset($node) || !$node->hasField('field_alternative_title') || !$node->hasField('field_summary')) {
    return [];
  }

  return [
    '[remora:seo_title]' => $node->get('field_alternative_title')->value ?: $node->label(),
    '[remora:seo_description]' => $node->get('field_summary')->value ?: '',
  ];
}
