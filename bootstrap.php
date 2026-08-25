<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die('Desculpe. Você não pode acessar este arquivo diretamente.');
}

if (!defined('PLUGIN_DASHBOARDPLUS_VERSION')) {
   define('PLUGIN_DASHBOARDPLUS_VERSION', '1.2.3');
}

if (!defined('PLUGIN_DASHBOARDPLUS_SCHEMA_VERSION')) {
   define('PLUGIN_DASHBOARDPLUS_SCHEMA_VERSION', '1.1.2');
}

if (!defined('PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION')) {
   define('PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION', '10.0.24');
}

if (!defined('PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION')) {
   define('PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION', '10.0.99');
}

if (!defined('PLUGIN_DASHBOARDPLUS_DIR')) {
   define('PLUGIN_DASHBOARDPLUS_DIR', __DIR__);
}

$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
   require_once $composer_autoload;
}

if (!defined('PLUGIN_DASHBOARDPLUS_AUTOLOADER')) {
   define('PLUGIN_DASHBOARDPLUS_AUTOLOADER', true);

   spl_autoload_register(static function(string $class): void {
      $prefix = 'GlpiPlugin\\Dashboardplus\\';
      if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
         return;
      }

      $relative = substr($class, strlen($prefix));
      $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
      if (file_exists($file)) {
         require_once $file;
      }
   });
}
