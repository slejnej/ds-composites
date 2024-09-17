<?php

namespace Drupal\remora_core\Service;

use Drupal;

/**
 * Helps modules with basic template functionality on _theme hook
 */
class ModuleTemplateService
{

  /**
   * Generates a theme entry for every .html.twig entry in the /templates directory of the specified module
   *
   * @param string $moduleName
   * @param array $variables An optional entry per themeHook defining the variables to be passed to the template
   * @return array
   */
  public function generateThemesFromTemplates(string $moduleName, array $variables = []): array
  {
    $fs = Drupal::service('file_system');
    $templatesPath = $fs->realpath("$moduleName://templates");
    $templates = $fs->scanDirectory($templatesPath, '/\.html.twig$/');
    $theme = [];

    // loop over all the twig files in the directory
    foreach ($templates as $template) {
      $templateName = str_replace('.html', '', $template->name);
      $themeHook = str_replace('-', '_', $templateName);

      // strip DOCUMENT_ROOT/templates from the beginning
      $pathInModule = str_replace($templatesPath, '', $template->uri);

      // get parts needed for base hook value
      $parts = explode('__', $themeHook);
      $templateBaseHook = $parts[0];

      // strip filename from the end
      $pathInModule = str_replace('/' . $template->filename, '', $pathInModule);
      // add whatever is left to the module path
      $pathToTemplate = "@$moduleName/templates" . $pathInModule;

      $theme[$themeHook] = [
        'template' => $templateName,
        'path' => $pathToTemplate,
        'variables' => $variables[$themeHook] ?? [],
        'base hook' => $templateBaseHook
      ];
    }

    return $theme;
  }

}
