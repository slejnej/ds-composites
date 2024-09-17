<?php

require '../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$app_root = '.';
$site_path = 'sites/default';
$settingsFile = './sites/default/settings.local.php';

$response = ['settings' => 'OK'];

if (! file_exists($settingsFile)) {
  $response['settings'] = 'Not found';
  echo json_encode($response);
  return;
}

include_once $settingsFile;

$dbSettings = $databases['default']['default'] ?? null;
$mongoSettings = $config['mongodb_connections']['default'] ?? null;

if ($dbSettings) {
  try {
    $conn = new PDO(sprintf('mysql:host=%s;dbname=%s', $dbSettings['host'], $dbSettings['database']),
      $dbSettings['username'], $dbSettings['password']);

    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $response['MariaDB'] = 'OK';
  } catch (PDOException $e) {
    $response['MariaDB'] = "Connection failed: " . $e->getMessage();
  }
}

if ($mongoSettings) {
  try {
    $connectionUrl = sprintf('mongodb://%s:%s', $mongoSettings['host'], $mongoSettings['port']);
    $connection = new MongoDB\Driver\Manager($connectionUrl);
    $response['MongoDB'] = 'OK';
  } catch (Throwable $e) {
    $response['MongoDB'] = "Connection failed: " . $e->getMessage();
  }
}

echo json_encode($response);