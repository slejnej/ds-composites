<?php

namespace Drupal\installation_profile\Util;

use Drupal\Core\Serialization\Yaml;

/**
 * Converts SCSS back to JSON with mapping synchronization capabilities
 */
class ScssToJsonConverter
{
  private array $mapping;
  private array $unmappedVariables = [];
  private array $missingVariables = [];
  private array $movedVariables = [];
  private array $foundInOtherPalettes = [];

  public function __construct(string $mappingFile)
  {
    if (!file_exists($mappingFile)) {
      throw new \RuntimeException("Mapping file not found: $mappingFile");
    }

    $content = file_get_contents($mappingFile);
    $data = Yaml::decode($content);
    $this->mapping = $data['variable_mapping'] ?? [];

    if (empty($this->mapping)) {
      throw new \RuntimeException("No mapping data found in: $mappingFile");
    }
  }

  /**
   * Convert SCSS files back to JSON with mapping analysis
   */
  public function convertWithAnalysis(string $themePath): array
  {
    $scssPath = $themePath . '/assets/scss/variables';
    $this->resetAnalysis();

    if (!is_dir($scssPath)) {
      throw new \RuntimeException("SCSS directory not found: $scssPath");
    }

    // Load all SCSS variables from all palettes
    $allScssVariables = $this->loadAllScssVariables($scssPath);

    $jsonData = [];
    $analysis = [
      'missing' => [],
      'moved' => [],
      'new' => [],
      'orphaned' => []
    ];

    foreach ($this->mapping as $palette => $paletteMapping) {
      $scssFile = $scssPath . '/_' . $palette . '.scss';

      if (!file_exists($scssFile)) {
        $analysis['missing']['palettes'][] = $palette;
        continue;
      }

      $variables = $allScssVariables[$palette] ?? [];
      $jsonStructure = $this->buildJsonFromMapping($palette, $variables, $paletteMapping, $allScssVariables);
      $jsonFilename = $this->getOutputJsonFilename($palette);
      $jsonData[$jsonFilename] = $jsonStructure;

      // Analyze this palette
      $paletteAnalysis = $this->analyzePalette($palette, $paletteMapping, $allScssVariables);
      $analysis = array_merge_recursive($analysis, $paletteAnalysis);
    }

    return [$jsonData, $analysis];
  }

  /**
   * Get the output JSON filename for a palette
   */
  private function getOutputJsonFilename(string $palette): string
  {
    if (isset($this->sources[$palette])) {
      return $this->sources[$palette];
    }

    if ($palette === 'global') {
      return 'primitives.json';
    }

    return $palette . '.tokens.json';
  }

  /**
   * Load all SCSS variables from all files
   */
  private function loadAllScssVariables(string $scssPath): array
  {
    $allVariables = [];
    $files = scandir($scssPath);

    foreach ($files as $file) {
      if ($file === '.' || $file === '..' || !str_starts_with($file, '_')) {
        continue;
      }

      $palette = substr(basename($file, '.scss'), 1);
      $filePath = $scssPath . '/' . $file;

      if (is_file($filePath)) {
        $allVariables[$palette] = $this->parseScssFile($filePath); // Only 1 argument here
      }
    }

    return $allVariables;
  }

  /**
   * Parse SCSS file to get variables
   */
  private function parseScssFile(string $filePath): array
  {
    $content = file_get_contents($filePath);
    $variables = [];
    $lines = explode("\n", $content);

    foreach ($lines as $line) {
      $line = trim($line);

      // Skip comments, imports, empty lines
      if (empty($line) ||
        str_starts_with($line, '//') ||
        str_starts_with($line, '/*') ||
        str_starts_with($line, '@import')) {
        continue;
      }

      // Match: $variable-name: value;
      if (preg_match('/^\$([a-z0-9_-]+)\s*:\s*(.+?);$/i', $line, $matches)) {
        $variables[$matches[1]] = trim($matches[2]);
      }
    }

    return $variables;
  }

  /**
   * Analyze a single palette's mapping vs actual SCSS
   */
  private function analyzePalette(string $palette, array $paletteMapping, array $allScssVariables): array
  {
    $currentScss = $allScssVariables[$palette] ?? [];
    $analysis = [
      'missing' => [],
      'moved' => [],
      'new' => [],
      'orphaned' => []
    ];

    // Check each mapped variable
    foreach ($paletteMapping as $mappedVar => $map) {
      if (!isset($currentScss[$mappedVar])) {
        // Variable not in current palette - check if it moved to another palette
        $foundIn = $this->findVariableInOtherPalettes($mappedVar, $palette, $allScssVariables);

        if ($foundIn) {
          $analysis['moved'][] = [
            'variable' => $mappedVar,
            'from' => $palette,
            'to' => $foundIn['palette'],
            'path' => $map['path'],
            'type' => $map['type']
          ];
        } else {
          $analysis['missing'][] = [
            'variable' => $mappedVar,
            'palette' => $palette,
            'path' => $map['path'],
            'type' => $map['type']
          ];
        }
      }
    }

    // Check for new variables (in SCSS but not in mapping)
    foreach ($currentScss as $varName => $value) {
      if (!isset($paletteMapping[$varName])) {
        // Check if this might be a moved variable from another palette
        $origin = $this->findInMapping($varName);

        if ($origin) {
          $analysis['moved'][] = [
            'variable' => $varName,
            'from' => $origin['palette'],
            'to' => $palette,
            'path' => $origin['path'],
            'type' => $origin['type']
          ];
        } else {
          $analysis['new'][] = [
            'variable' => $varName,
            'palette' => $palette,
            'value' => $value
          ];
        }
      }
    }

    return $analysis;
  }

  /**
   * Find variable in other palettes
   */
  private function findVariableInOtherPalettes(string $varName, string $excludePalette, array $allScssVariables): ?array
  {
    foreach ($allScssVariables as $palette => $variables) {
      if ($palette === $excludePalette) {
        continue;
      }

      if (isset($variables[$varName])) {
        return [
          'palette' => $palette,
          'value' => $variables[$varName]
        ];
      }
    }

    return null;
  }

  /**
   * Find variable in mapping (any palette)
   */
  private function findInMapping(string $varName): ?array
  {
    foreach ($this->mapping as $palette => $mapping) {
      if (isset($mapping[$varName])) {
        return [
          'palette' => $palette,
          'path' => $mapping[$varName]['path'],
          'type' => $mapping[$varName]['type']
        ];
      }
    }

    return null;
  }

  /**
   * Build JSON structure (same as before but with tracking)
   */
  private function buildJsonFromMapping(string $palette, array $scssVariables, array $mapping, array $allScssVariables): array
  {
    $jsonStructure = [];

    foreach ($scssVariables as $scssVarName => $scssValue) {
      if (!isset($mapping[$scssVarName])) {
        // Try to find in other palette mappings
        $foundMapping = $this->findInMapping($scssVarName);

        if ($foundMapping && $foundMapping['palette'] !== $palette) {
          // This variable moved from another palette - use its original mapping
          $this->movedVariables[] = [
            'variable' => $scssVarName,
            'from' => $foundMapping['palette'],
            'to' => $palette,
            'path' => $foundMapping['path'],
            'type' => $foundMapping['type']
          ];

          $map = $foundMapping;
          $type = $foundMapping['type']; // Use type from mapping
        } else {
          // New variable - skip
          continue;
        }
      } else {
        $map = $mapping[$scssVarName];
        $type = $map['type']; // Use type from mapping
      }

      $jsonValue = $this->cleanValue($scssValue, $palette);

      // DON'T determine type from value - use the type from mapping
      // $actualType = $this->determineType($jsonValue); // REMOVE THIS

      $this->insertAtPath($jsonStructure, $map['path'], $jsonValue, $type); // Use $type, not $actualType
    }

    return $jsonStructure;
  }

  /**
   * Clean value for JSON
   */
  private function cleanValue(string $value, string $currentPalette): string|int|float
  {
    // Handle variable references
    if (str_starts_with($value, '$')) {
      return $this->convertVariableReference($value, $currentPalette);
    }

    // Handle rem values (convert back to px)
    if (str_ends_with($value, 'rem')) {
      $rem = (float) substr($value, 0, -3);
      $px = $rem * 16;
      return $px == (int) $px ? (int) $px : $px;
    }

    // Handle px values (strip unit)
    if (str_ends_with($value, 'px')) {
      $num = substr($value, 0, -2);
      return is_numeric($num) ? (float) $num : $value;
    }

    // Handle plain numbers
    if (is_numeric($value)) {
      $num = (float) $value;
      return $num == (int) $num ? (int) $num : $num;
    }

    // Handle quoted strings
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
      (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
      return substr($value, 1, -1);
    }

    return $value;
  }

  /**
   * Convert SCSS variable reference to JSON reference
   */
  private function convertVariableReference(string $value, string $currentPalette): string
  {
    $varName = substr($value, 1);

    // Find this variable in mapping to get its original path
    foreach ($this->mapping as $palette => $paletteMapping) {
      foreach ($paletteMapping as $scssVar => $map) {
        if ($scssVar === $varName) {
          // Found it! Convert to JSON reference format
          $path = $map['path'];
          if ($palette !== 'global') {
            $path = $palette . '.' . $path;
          }
          return '{' . $path . '}';
        }
      }
    }

    // If not found in mapping, try to guess (fallback)
    // Remove current palette prefix if present
    if ($currentPalette !== 'global' && str_starts_with($varName, $currentPalette . '-')) {
      $varName = substr($varName, strlen($currentPalette) + 1);
    }

    // Convert hyphens to dots
    return '{' . str_replace('-', '.', $varName) . '}';
  }

  /**
   * Insert value at dotted path in array
   */
  private function insertAtPath(array &$array, string $path, $value, string $typeFromMapping): void
  {
    $parts = explode('.', $path);
    $current = &$array;

    foreach ($parts as $i => $part) {
      if ($i === count($parts) - 1) {
        // Last part - set the value with type FROM MAPPING
        $current[$part] = [
          '$type' => $typeFromMapping, // Use type from mapping
          '$value' => $value
        ];
      } else {
        // Intermediate part - create array if needed
        if (!isset($current[$part]) || !is_array($current[$part])) {
          $current[$part] = [];
        }
        $current = &$current[$part];
      }
    }
  }

  /**
   * Fix mapping based on analysis
   */
  public function fixMapping(string $mappingFile, array $analysis): void
  {
    $fixedMapping = $this->mapping;

    // Remove missing variables
    foreach ($analysis['missing'] ?? [] as $missing) {
      if (isset($fixedMapping[$missing['palette']][$missing['variable']])) {
        unset($fixedMapping[$missing['palette']][$missing['variable']]);
      }
    }

    // Move variables between palettes
    foreach ($analysis['moved'] ?? [] as $moved) {
      // Remove from old palette
      if (isset($fixedMapping[$moved['from']][$moved['variable']])) {
        unset($fixedMapping[$moved['from']][$moved['variable']]);
      }

      // Add to new palette
      $fixedMapping[$moved['to']][$moved['variable']] = [
        'path' => $moved['path'],
        'type' => $moved['type']
      ];
    }

    // Save fixed mapping
    $yaml = Yaml::encode(['variable_mapping' => $fixedMapping]);
    file_put_contents($mappingFile . '.backup', Yaml::encode(['variable_mapping' => $this->mapping])); // Backup
    file_put_contents($mappingFile, $yaml);

    // Update internal mapping
    $this->mapping = $fixedMapping;
  }

  /**
   * Reset analysis data
   */
  private function resetAnalysis(): void
  {
    $this->unmappedVariables = [];
    $this->missingVariables = [];
    $this->movedVariables = [];
    $this->foundInOtherPalettes = [];
  }

  /**
   * Save JSON data to files
   */
  public function saveToFiles(array $jsonData, string $outputDir): void
  {
    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0777, true);
    }

    foreach ($jsonData as $filename => $data) {
      $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      file_put_contents($outputDir . '/' . $filename, $json);
    }
  }
}
