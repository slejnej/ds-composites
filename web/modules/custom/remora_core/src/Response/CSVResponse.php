<?php

namespace Drupal\remora_core\Response;

use Drupal\remora_core\Util\StringUtil;
use Symfony\Component\HttpFoundation\Response;

class CSVResponse extends Response
{

  /**
   * Creates a response that the browser accepts as a downloadable file
   *
   * @param array $csvData The CSV data, formatted as a multidimensional array of strings. Example:
   *                       [
   *                         ['Header 1', 'Header 2'],
   *                         ['Row 1 value 1', 'Row 1 value 2'],
   *                         ...
   *                       ]
   * @param string $filename
   */
  public function __construct(array $csvData, string $filename)
  {
    $response = $this->formatResponse($csvData);
    $filename = StringUtil::sanitizeFilename($filename);

    parent::__construct($response, self::HTTP_OK);
    $this->headers->set('Content-Type', 'text/csv');
    $this->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
  }

  /**
   * Turns the CSV array into a CSV string
   *
   * @param array $csvData
   * @return string
   */
  private function formatResponse(array $csvData): string
  {
    $response = '';
    foreach($csvData as $row) {
      foreach($row as &$col) {
        // escape any double quotes
        $col = str_replace('"', '""', $col ?? '');

        // if the cell contains a comma or double quote, wrap it in double quotes
        if(str_contains($col, '"') || str_contains($col, ',')) {
          $col = sprintf('"%s"', $col);
        }
      }


      $rowText = implode(',', $row);
      $rowText = str_replace(PHP_EOL, '', $rowText);
      $response .= sprintf('%s%s', $rowText, PHP_EOL);
    }

    return rtrim($response, PHP_EOL);
  }
}
