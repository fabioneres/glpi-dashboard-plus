<?php

/**
 * Smoke test dos widgets de capacidade em uma instância GLPI.
 *
 * Uso:
 * php vm_capacity_smoke.php /var/www/html/glpi
 */

$glpi_root = $argv[1] ?? '/var/www/html/glpi';
if (!is_dir($glpi_root) || !file_exists($glpi_root . '/inc/includes.php')) {
   fwrite(STDERR, "Raiz do GLPI inválida: {$glpi_root}\n");
   exit(1);
}

chdir($glpi_root);
include $glpi_root . '/inc/includes.php';
require_once $glpi_root . '/plugins/dashboardplus/bootstrap.php';

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\DashboardContext;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;

Session::loadLanguage();

$context = DashboardContext::fromRequest([], Config::getSettings());
$keys = [
   'capacity_team_summary',
   'capacity_technician_load',
   'capacity_technician_load_table',
   'capacity_alerts',
];

$out = [];
foreach ($keys as $key) {
   $widget = WidgetRegistry::get($key);
   if (!$widget) {
      $out[$key] = ['ok' => false, 'error' => 'Widget não encontrado'];
      continue;
   }

   try {
      $data = $widget->getData($context);
      $out[$key] = [
         'ok'   => true,
         'keys' => array_keys($data),
         'rows' => isset($data['rows']) && is_array($data['rows']) ? count($data['rows']) : null,
      ];
   } catch (Throwable $e) {
      $out[$key] = [
         'ok'    => false,
         'error' => $e->getMessage(),
      ];
   }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
