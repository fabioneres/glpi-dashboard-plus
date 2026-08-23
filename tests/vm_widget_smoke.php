<?php

/**
 * Validação de fumaça dos widgets em uma instância GLPI.
 *
 * Uso:
 * php vm_widget_smoke.php /var/www/html/glpi
 */

$glpi_root = $argv[1] ?? '/var/www/html/glpi';
if (!is_dir($glpi_root) || !file_exists($glpi_root . '/inc/includes.php')) {
   fwrite(STDERR, "Raiz do GLPI inválida: {$glpi_root}\n");
   exit(1);
}

chdir($glpi_root);
include $glpi_root . '/inc/includes.php';
require_once $glpi_root . '/plugins/dashboardplus/bootstrap.php';

$_SESSION['glpiactive_entity'] = 0;
$_SESSION['glpiactive_entity_recursive'] = 1;
$_SESSION['glpiactiveentities'] = [0];
$_SESSION['glpiactiveentities_string'] = '0';
$_SESSION['glpiactiveprofile'] = ['id' => 4];

$context = new GlpiPlugin\Dashboardplus\DashboardContext(
   date('Y-m-d', strtotime('-30 days')),
   date('Y-m-d'),
   null,
   true,
   null,
   null,
   null,
   null,
   null,
   ['use_cache' => 0],
   []
);

$keys = [
   'tickets_unassigned',
   'tickets_solved_today',
   'notification_queue',
   'tickets_priority_medium',
   'tickets_priority_high',
   'tickets_priority_critical',
   'tickets_received_by_day',
   'tickets_solved_closed_by_day',
   'tickets_open_by_day',
   'tickets_by_location',
   'tickets_by_entity',
   'tickets_by_request_type',
   'sla_response_compliance',
   'sla_by_technician',
   'sla_by_category',
   'average_solve_time_closed',
   'satisfaction_average',
   'satisfaction_answered_count',
   'satisfaction_response_rate',
   'satisfaction_general_breakdown',
   'satisfaction_breakdown',
   'satisfaction_by_group_average',
   'satisfaction_by_category_summary',
   'satisfaction_comments',
   'satisfaction_answered_by_month',
   'satisfaction_average_by_month',
   'tickets_reopened',
   'tickets_pending_reasons',
   'task_effort_by_technician',
   'asset_total_computers',
   'asset_total_monitors',
   'asset_total_printers',
   'asset_total_phones',
   'asset_computers_by_manufacturer',
   'asset_monitors_by_manufacturer',
   'asset_computers_by_type',
   'asset_computers_by_location',
   'asset_computers_by_os',
   'asset_computers_by_cpu',
   'asset_computers_sp_map',
];

$out = [];
foreach ($keys as $key) {
   $widget = GlpiPlugin\Dashboardplus\Widget\WidgetRegistry::get($key);
   try {
      $data = $widget ? $widget->getData($context) : null;
      $html = ($widget && is_array($data))
         ? GlpiPlugin\Dashboardplus\Widget\WidgetRenderer::render($widget, $data)
         : '';
      $out[$key] = [
         'ok'   => is_array($data) && is_string($html) && $html !== '',
         'keys' => is_array($data) ? array_keys($data) : [],
      ];
   } catch (Throwable $e) {
      $out[$key] = [
         'ok'    => false,
         'error' => $e->getMessage(),
      ];
   }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
