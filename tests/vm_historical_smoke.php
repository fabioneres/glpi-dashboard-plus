<?php
/**
 * Smoke test for Dashboard Plus historical period support.
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
$_SESSION['glpiactive_entity'] = 0;
$_SESSION['glpiactive_entity_recursive'] = 1;
$_SESSION['glpiactiveentities'] = [0];
$_SESSION['glpiactiveentities_string'] = '0';
$_SESSION['glpiactiveprofile'] = ['id' => 4];

$context = DashboardContext::fromRequest([
   'period_days' => '0',
], Config::getSettings());

$keys = [
   'tickets_open',
   'tickets_solved',
   'tickets_closed',
   'tickets_monthly_evolution',
];

$out = [
   'period_days' => $context->getPeriodDays(),
   'start'       => $context->getStart(),
   'end'         => $context->getEnd(),
   'widgets'     => [],
];

foreach ($keys as $key) {
   $widget = WidgetRegistry::get($key);
   $data = $widget ? $widget->getData($context) : null;
   $out['widgets'][$key] = [
      'ok'   => is_array($data),
      'keys' => is_array($data) ? array_keys($data) : [],
   ];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
