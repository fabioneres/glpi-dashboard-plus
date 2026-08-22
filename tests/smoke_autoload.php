<?php

/**
 * Teste mínimo do autoloader de fallback do plugin.
 *
 * Execute em um ambiente de teste com GLPI root ou com GLPI_ROOT definido.
 */

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(__DIR__, 3));
}

require_once dirname(__DIR__) . '/bootstrap.php';

$classes = [
   GlpiPlugin\Dashboardplus\Config::class,
   GlpiPlugin\Dashboardplus\Dashboard::class,
   GlpiPlugin\Dashboardplus\Widget\WidgetRegistry::class,
   GlpiPlugin\Dashboardplus\Provider\TicketMetricsProvider::class,
];

foreach ($classes as $class) {
   if (!class_exists($class)) {
      fwrite(STDERR, "Classe ausente: {$class}\n");
      exit(1);
   }
}

echo "Teste de autoload do Dashboard Plus aprovado.\n";
