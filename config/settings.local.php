<?php
$settings['hash_salt'] = 'PtXEvtTeUkb6pXYyePdJED0Ryz-2G9NjJF6eGdjnKMGz6glCpGtloT1RD3tN09qfBG8yEdg6nQ';

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/development.services.yml';

$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;

$settings['config_sync_directory'] = '../config/sync';
$databases['default']['default'] = array (
  'database' => 'drupal10',
  'username' => 'drupal10',
  'password' => 'drupal10',
  'prefix' => '',
  'host' => 'database',
  'port' => '3306',
  'isolation_level' => 'READ COMMITTED',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
);

$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

$settings['skip_permissions_hardening'] = TRUE;

$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

$config['system.logging']['error_level'] = 'verbose';
$settings['file_private_path'] = '/app/web/sites/default/private';
$settings['file_temp_path'] = '/tmp';

$settings['trusted_host_patterns'] = [
  '^localhost$',
  '127\.0\.0\.1',
  '^ds-composites\.lndo\.site$'
];

# these variables will override solr config. If the projec does not have SOLR, remove these
# server is machine name for SOLR server in search API
# don't forget to install the patch from gists for search API override settings!
$settings['search_api_solr'] = [
  'solr_server' => [
    'scheme' => 'http',
    'host' => 'search',
    'port' => 8983,
    'path' => '/',
    'core' => 'ds-composites_develop'
  ]
];
