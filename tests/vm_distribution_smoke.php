<?php
/**
 * Focused VM smoke test for Dashboard Plus distribution widgets.
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
use GlpiPlugin\Dashboardplus\Widget\WidgetRenderer;

Session::loadLanguage();
$_SESSION['glpiactiveentities'] = $_SESSION['glpiactiveentities'] ?? [0];
$_SESSION['glpiactiveentities_string'] = $_SESSION['glpiactiveentities_string'] ?? '0';
$_SESSION['glpiactive_entity_recursive'] = $_SESSION['glpiactive_entity_recursive'] ?? 1;

$context = DashboardContext::fromRequest([], Config::getSettings());
$result = [];

foreach (WidgetRegistry::getAll() as $key => $widget) {
   if (strpos($key, 'distribution_') !== 0) {
      continue;
   }

   $data = $widget->getData($context);
   $html = WidgetRenderer::render($widget, $data, [
      'limit'       => 10,
      'show_labels' => 1,
      'gradient'    => 1,
      'color'       => '#2563eb',
      'background'  => '#fafafa',
   ]);

   $result[$key] = [
      'ok'      => is_array($data) && $html !== '',
      'keys'    => array_keys($data),
      'html'    => strlen($html),
      'canView' => $widget->canView(),
   ];
}

$chart_widget = WidgetRegistry::get('distribution_actuation');
if ($chart_widget !== null) {
   $chart_data = $chart_widget->getData($context);
   $visualizations = WidgetRegistry::getAvailableVisualizations($chart_widget);
   $result['_visualizations'] = [];
   foreach ($visualizations as $visualization => $label) {
      $html = WidgetRenderer::render($chart_widget, $chart_data, [
         'visualization' => $visualization,
         'limit'         => 10,
         'show_labels'   => 1,
         'gradient'      => 1,
         'color'         => '#2563eb',
         'background'    => '#fafafa',
      ]);
      $result['_visualizations'][$visualization] = [
         'label' => $label,
         'ok'    => $html !== '',
         'html'  => strlen($html),
      ];
   }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
