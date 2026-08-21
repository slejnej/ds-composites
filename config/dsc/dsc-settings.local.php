<?php
$settings['hash_salt'] = 'PtXEvtTeUkb6pXYyePdJED0Ryz-2G9NjJF6eGdjnKMGz6glCpGtloT1RD3tN09qfBG8yEdg6nQ';

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;

$settings['config_sync_directory'] = '../config/sync-dsc';
$databases['default']['default'] = array (
  'database' => 'xvdjymqf_dsc',
  'username' => 'xvdjymqf_dsc_dbuser',
  'password' => 'sa#5&P9Qlz6Iad8R',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'isolation_level' => 'READ COMMITTED',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
);

$settings['skip_permissions_hardening'] = TRUE;

$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Disable error display for anonymous users.
$config['system.logging']['error_level'] = 'critical';

$settings['file_private_path'] = '/home/ubuntu/shared-folders/private/ds-composites/ds-composites';
$settings['file_temp_path'] = '/home/ubuntu/shared-folders/temp/ds-composites/ds-composites';
$settings['file_public_base_url'] = 'https://ds-composites.eu/sites/ds-composites/files';

$settings['trusted_host_patterns'] = [
  '^localhost$',
  '127\.0\.0\.1',
  '^ds-composites\.lndo\.site$',
  '^ds-composites\.2cent2\.eu$',
  '^ds-composites\.eu$',
];

// to be able to get from variable in TWIG
$settings['current_multisite'] = 'dsc';

# empty on all environments EXCEPT production
$settings['simple_sitemap_engines.index_now.key'] = 'ad6cd4e4017b49029ea554875c612624';

# reduce cache tables in DB from 5000
$settings['database_cache_max_rows']['bins']['page'] = 1000;

$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = [
  '172.31.0.0/16',
];
$settings['reverse_proxy_trusted_headers'] =
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;

/**
 * smtp_host = SMTP server
 * smtp_port = SMTP port
 * smtp_protocol = SMTP protocol (ssl or tls)
 * smtp_username = SMTP username
 * smtp_password = SMTP password
 * smtp_on = Enable SMTP
 * smtp_from = 'From' email address
 * smtp_fromname = 'From' name
 * smtp_keepalive = Keep SMTP connection alive
 * smtp_allowhtml = Allow HTML in emails
 *
 * @var array $config
 */
$config['smtp.settings'] = [
  'smtp_host' => 'mail.ds-composites.eu',
  'smtp_port' => 465,
  'smtp_protocol' => 'ssl',
  'smtp_username' => 'info@ds-composites.eu',
  'smtp_password' => '',
  'smtp_on' => 1,
  'smtp_from' => 'info@ds-composites.eu',
  'smtp_fromname' => 'DS Kompoziti d.o.o. - ds-composites.eu',
  'smtp_keepalive' => false,
  'smtp_allowhtml' => true
];
