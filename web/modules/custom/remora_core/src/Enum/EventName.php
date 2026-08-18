<?php

namespace Drupal\remora_core\Enum;

enum EventName: string
{
  /** CONTENT TYPE REFERENCE EVENTS */
  case REFERENCEABLE_CONTENT_TYPE_INDEX = 'remora_referenceable_ct_index';


  /** PARAGRAPH REGION REFERENCE EVENTS */
  case REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX = 'remora_referenceable_paragraph_type_main_content_index';
  case REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX = 'remora_referenceable_paragraph_type_footer_index';
  case REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX = 'remora_referenceable_paragraph_type_hero_content_index';
  case REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX = 'remora_referenceable_paragraph_type_sidebar_index';
  case REFERENCEABLE_PARAGRAPH_TYPE_POSTSCRIPT_INDEX = 'remora_referenceable_paragraph_type_postscript_index';


  /** MEDIA TYPE REFERENCE EVENTS */
  case REFERENCEABLE_MEDIA_TYPE_IMAGE_VIDEO_INDEX = 'remora_referenceable_media_type_image_video_index';
  case REFERENCEABLE_MEDIA_TYPE_IMAGE_INDEX = 'remora_referenceable_media_type_image_index';
  case REFERENCEABLE_MEDIA_TYPE_VIDEO_INDEX = 'remora_referenceable_media_type_video_index';

}
