<?php

namespace Drupal\installation_profile\Drush\Commands;

use Drupal\installation_profile\Util\ScssToJsonConverter;
use Drush\Attributes\Command;
use Drush\Commands\DrushCommands;

/**
 * Drush command file
 *
 * Class ScssToJsonCommand
 * @package Drupal\installation_profile\Drush\Commands
 */
class ScssToJsonCommand extends DrushCommands
{
  #[Command(name: 'theme:scss-to-json', aliases: ['stoj'])]
  public function scss_to_json($themePath, $outputDir, $options = [
    'fix' => false,
    'dry-run' => false
  ])
  {
    // Ensure theme path is absolute
    if (!str_starts_with($themePath, '/')) {
      $themePath = DRUPAL_ROOT . '/' . ltrim($themePath, '/');
    }

    $mappingFile = $themePath . '/config/scss_mapping.yml';

    if (!file_exists($mappingFile)) {
      $this->logger()->error('Mapping file not found. Generate SCSS first with form upload.');
      $this->logger()->error('Expected mapping at: ' . $mappingFile);
      return self::EXIT_FAILURE;
    }

    try {
      $converter = new ScssToJsonConverter($mappingFile);
      [$jsonData, $analysis] = $converter->convertWithAnalysis($themePath);

      // Show analysis
      $this->io()->title('Mapping Analysis');

      // Show missing variables
      if (!empty($analysis['missing'])) {
        $this->io()->section('Missing Variables (in mapping but not in any SCSS file)');
        foreach ($analysis['missing'] as $missing) {
          $this->io()->text("  - {$missing['variable']} (palette: {$missing['palette']}, path: {$missing['path']})");
        }
      }

      // Show moved variables
      if (!empty($analysis['moved'])) {
        $this->io()->section('Moved Variables (changed palette)');
        foreach ($analysis['moved'] as $moved) {
          $this->io()->text("  - {$moved['variable']}: {$moved['from']} → {$moved['to']} (path: {$moved['path']})");
        }
      }

      // Show new variables
      if (!empty($analysis['new'])) {
        $this->io()->section('New Variables (in SCSS but not in mapping)');
        foreach ($analysis['new'] as $new) {
          $this->io()->text("  - {$new['variable']} (palette: {$new['palette']}, value: {$new['value']})");
        }
      }

      // Show orphaned palettes
      if (!empty($analysis['missing']['palettes'])) {
        $this->io()->section('Missing SCSS Files');
        foreach ($analysis['missing']['palettes'] as $palette) {
          $this->io()->text("  - {$palette}.scss not found");
        }
      }

      // Generate JSON output
      $converter->saveToFiles($jsonData, $outputDir);

      $this->io()->newLine();
      $this->io()->success('JSON files generated: ' . $outputDir);

      // Handle fixes
      $hasIssues = !empty($analysis['missing']) || !empty($analysis['moved']) || !empty($analysis['new']);

      if ($hasIssues) {
        if ($options['dry-run']) {
          $this->io()->note('Dry run: Mapping would be fixed but no changes made');
          return self::EXIT_SUCCESS;
        }

        if ($options['fix']) {
          // Auto-fix
          $converter->fixMapping($mappingFile, $analysis);
          $this->io()->success('Mapping file automatically fixed: ' . $mappingFile);
          $this->io()->note('Original mapping backed up to: ' . $mappingFile . '.backup');
        } else {
          // Ask for confirmation
          if ($this->io()->confirm('Fix mapping file automatically?', false)) {
            $converter->fixMapping($mappingFile, $analysis);
            $this->io()->success('Mapping file fixed: ' . $mappingFile);
            $this->io()->note('Original mapping backed up to: ' . $mappingFile . '.backup');
          }
        }
      } else {
        $this->io()->success('No mapping issues found.');
      }

      return self::EXIT_SUCCESS;

    } catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }
  }
}
