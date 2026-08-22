<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Installer;
use GlpiPlugin\Dashboardplus\Profile;

if (!defined('GLPI_ROOT')) {
   die('Desculpe. Você não pode acessar este arquivo diretamente.');
}

require_once __DIR__ . '/bootstrap.php';

function plugin_dashboardplus_install(): bool {
   return Installer::install();
}

function plugin_dashboardplus_upgrade($old_version): bool {
   return Installer::install();
}

function plugin_dashboardplus_uninstall(): bool {
   $result = Installer::uninstall();
   if ($result) {
      ProfileRight::deleteProfileRights(Config::getRightNames());
      Profile::removeRightsFromSession();
   }

   return $result;
}
