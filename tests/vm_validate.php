<?php

/**
 * Auxiliar de validação em tempo de execução para uma instância GLPI.
 *
 * Uso:
 * php vm_validate.php /var/www/html/glpi
 */

$glpi_root = $argv[1] ?? '/var/www/html/glpi';
if (!is_dir($glpi_root) || !file_exists($glpi_root . '/inc/includes.php')) {
   fwrite(STDERR, "Raiz do GLPI inválida: {$glpi_root}\n");
   exit(1);
}

chdir($glpi_root);
include $glpi_root . '/inc/includes.php';

global $DB;

$tables = [
   'glpi_plugin_dashboardplus_configs',
   'glpi_plugin_dashboardplus_configentities',
   'glpi_plugin_dashboardplus_entityconfigs',
   'glpi_plugin_dashboardplus_widgetconfigs',
];

$rights = [
   'plugin_dashboardplus_view',
   'plugin_dashboardplus_admin',
   'plugin_dashboardplus_widgets',
   'plugin_dashboardplus_global',
];

$out = [
   'glpi_version' => defined('GLPI_VERSION') ? GLPI_VERSION : null,
   'plugin'       => null,
   'tables'       => [],
   'rights'       => [],
   'widgets'      => [],
   'fields'       => [],
   'checks'       => [],
];

$plugin = $DB->request([
   'FROM'  => 'glpi_plugins',
   'WHERE' => ['directory' => 'dashboardplus'],
   'LIMIT' => 1,
])->current();

if ($plugin) {
   $out['plugin'] = [
      'id'      => (int) $plugin['id'],
      'name'    => (string) $plugin['name'],
      'version' => (string) $plugin['version'],
      'state'   => (int) $plugin['state'],
   ];
}

foreach ($tables as $table) {
   $exists = $DB->tableExists($table);
   $out['tables'][$table] = [
      'exists' => $exists,
      'count'  => $exists ? count(iterator_to_array($DB->request(['FROM' => $table]))) : null,
   ];
}

if ($DB->tableExists('glpi_plugin_dashboardplus_configs')) {
   foreach (['entity_default_enabled', 'default_enabled_tabs', 'capacity_enabled', 'capacity_cache_ttl', 'capacity_config'] as $field) {
      $out['fields'][$field] = $DB->fieldExists('glpi_plugin_dashboardplus_configs', $field);
   }
}

foreach ($rights as $right) {
   $out['rights'][$right] = count(iterator_to_array($DB->request([
      'FROM'  => 'glpi_profilerights',
      'WHERE' => ['name' => $right],
   ])));
}

if ($DB->tableExists('glpi_plugin_dashboardplus_widgetconfigs')) {
   foreach ($DB->request([
      'FROM'  => 'glpi_plugin_dashboardplus_widgetconfigs',
      'ORDER' => ['display_order ASC'],
   ]) as $row) {
      $out['widgets'][] = [
         'key'     => (string) $row['widget_key'],
         'enabled' => (int) $row['is_enabled'],
         'order'   => (int) $row['display_order'],
         'width'   => (int) $row['width'],
      ];
   }
}

$out['checks'] = [
   'plugin_installed' => $out['plugin'] !== null,
   'plugin_active'    => $out['plugin'] !== null && (int) $out['plugin']['state'] === 1,
   'config_created'   => ($out['tables']['glpi_plugin_dashboardplus_configs']['count'] ?? 0) >= 1,
   'widgets_created'  => ($out['tables']['glpi_plugin_dashboardplus_widgetconfigs']['count'] ?? 0) >= 16,
   'rights_created'   => array_sum($out['rights']) >= 4,
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
