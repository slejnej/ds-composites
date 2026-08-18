<?php

namespace Drupal\security\Drush\Commands;

use Drush\Attributes\Command;
use Drush\Commands\DrushCommands;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Custom Drush command to dump SQL with anonymization process
 */
class AnonymizeSqlDumpCommands extends DrushCommands
{
  private const ANONYMIZED_EMAIL = "@mantaraymedia.co.uk";

  private const CACHE_TABLES = [
    'cache_config',
    'cache_data',
    'cache_default',
    'cache_dynamic_page_cache',
    'cache_entity',
    'cache_menu',
    'cache_render',
    'cache_toolbar',
    'cache_container',
  ];

  /**
   * Custom anonymize SQL dump command.
   *
   * @param array $options
   *   Options passed to the command.
   *
   * @command sql:dump-anonymize
   * @aliases sda
   * @option result-file The file where the SQL dump should be saved.
   */
  #[Command(name: 'sql:dump-anonymize', aliases: ['sda'])]
  public function anonymizeSqlDump(array $options = ['result-file' => self::REQ]): void
  {
    if (empty($options['result-file'])) {
      throw new \InvalidArgumentException("The --result-file option is required.");
    }

    // Get the result-file option
    $resultFile = $options['result-file'];

    $this->output()->writeln("<info>Result file: $resultFile</info>");
    $this->output()->writeln("<info>Downloading original DB data...</info>");

    $tablesToProcess = [
      'users_field_data' => 'anonymizeData',
      'webform_submission' => 'removeData',
      'webform_submission_data' => 'removeData',
      'watchdog' => 'removeData',
    ];

    // Add cache tables to removeData
    foreach (self::CACHE_TABLES as $cacheTable) {
      $tablesToProcess[$cacheTable] = 'removeData';
    }

    // run original sql:dump
    $process = new Process(['../vendor/drush/drush/drush', 'sql:dump']);

    try {
      $process->mustRun();
      $output = $process->getOutput();

      // Manipulate the output (e.g., anonymize user data or remove webform submissions, remove cache tables)
      $this->output()->writeln("<info>Anonymizing data...</info>");
      $manipulatedOutput = $this->manipulateOutput($output, $tablesToProcess);

      // Save manipulated output to the result file
      file_put_contents($resultFile, $manipulatedOutput);

      $this->output()->writeln("<info>SQL dump completed and anonymized. File saved to: $resultFile</info>");
    } catch (ProcessFailedException $exception) {
      $this->output()->writeln("<error>Failed to execute drush sql:dump command. Error: " . $exception->getMessage() . "</error>");
    }
  }

  /**
   * Manipulate SQL dump output based on specified tables to process.
   *
   * @param string $output
   *   The original SQL dump output.
   * @param array $tablesToProcess
   *   An array of tables and their processing type.
   *
   * @return string
   *   The manipulated SQL dump output with processed tables.
   */
  protected function manipulateOutput(string $output, array $tablesToProcess): string
  {
    $lines = explode("\n", $output);
    $anonIndex = 1;

    $processedLines = [];
    $insideTable = null;

    foreach ($lines as $line) {
      $trimmedLine = trim($line);

      // Detect the start of an INSERT INTO block
      if (preg_match('/^INSERT INTO `([^`]+)` VALUES/', $trimmedLine, $matches)) {
        $table = $matches[1];

        if (isset($tablesToProcess[$table])) {
          $insideTable = $table;

          if (!str_contains($table, 'cache')) {
            $this->output()->writeln("<info>Processing `$table` table ({$tablesToProcess[$table]})</info>");
          }

          if ($tablesToProcess[$table] === 'removeData') {
            // Skip the entire INSERT INTO block for removeData tables
            continue;
          }
        } else {
          $insideTable = null;
        }
      }

      // If we are inside a table that should be processed
      if ($insideTable !== null) {
        if ($trimmedLine === 'commit;') {
          if ($tablesToProcess[$insideTable] === 'removeData') {
            $processedLines[] = 'UNLOCK TABLES;';
          }
          if (!str_contains($table, 'cache')) {
            $this->output()->writeln("<info>Finished processing `$insideTable` table.</info>");
          }
          $insideTable = null;
        } elseif ($tablesToProcess[$insideTable] === 'removeData') {
          // Skip all insert lines for removeData tables
          continue;
        } elseif ($tablesToProcess[$insideTable] === 'anonymizeData') {
          // Process anonymization for anonymizeData tables
          $anonymizedLine = $this->anonymizeLine($line, $anonIndex);
          if ($anonymizedLine !== null) {
            $line = $anonymizedLine;
            $anonIndex++;
          }
        }
      }

      // Store the line in the final output if it's not skipped
      $processedLines[] = $line;
    }

    return implode("\n", $processedLines);
  }

  /**
   * Anonymize a line of SQL dump data.
   *
   * @param string $line The line of SQL dump data to anonymize.
   * @param int $anonIndex The index to use for generating anonymized values.
   *
   * @return string|null The anonymized line of SQL dump data, or null if no changes were made.
   */
  protected function anonymizeLine(string $line, int $anonIndex): ?string
  {
    // Match the pattern for the line, capturing username and email
    if (preg_match("/\(\d+,'[^']+','[^']+','[^']+',([^,]+),[^,]+,'([^']+)',/", $line, $matches)) {
      $username = trim($matches[1], "'");
      $email = trim($matches[2], "'");

      // Anonymize if the email is not the anonymized one already
      if (!str_contains($email, self::ANONYMIZED_EMAIL)) {
        // Replace username and email with anonymized values
        $line = str_replace("'$email'", "'anon{$anonIndex}@anonymized.com'", $line);
        return str_replace("'$username'", "'anon{$anonIndex}'", $line);
      }
    }

    return null;
  }

}
