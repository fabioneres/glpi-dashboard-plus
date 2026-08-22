<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Dashboard;
use GlpiPlugin\Dashboardplus\Menu;
use GlpiPlugin\Dashboardplus\Profile;

if (!defined('GLPI_ROOT')) {
   die('Desculpe. Você não pode acessar este arquivo diretamente.');
}

require_once __DIR__ . '/bootstrap.php';

function plugin_init_dashboardplus(): void {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['dashboardplus'] = true;
   $PLUGIN_HOOKS[Hooks::ADD_CSS]['dashboardplus'][] = 'css/dashboardplus-v2.css';
   $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['dashboardplus'][] = 'js/dashboardplus-v2.js';

   Plugin::loadLang('dashboardplus');

   if (!Plugin::isPluginActive('dashboardplus')) {
      return;
   }

   Plugin::registerClass(Profile::class, ['addtabon' => [\Profile::class]]);
   Plugin::registerClass(Menu::class);
   Plugin::registerClass(Dashboard::class);

   $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['dashboardplus'] = [Profile::class, 'initProfile'];

   if (Session::getLoginUserID()) {
      Profile::initProfile();

      $menu_cache_key = 'plugin_dashboardplus_menu_url_fix';
      if (($_SESSION[$menu_cache_key] ?? 0) !== 1) {
         unset($_SESSION['glpimenu']);
         $_SESSION[$menu_cache_key] = 1;
      }
   }

   if (Config::canView()) {
      $PLUGIN_HOOKS[Hooks::MENU_TOADD]['dashboardplus'] = [
         'plugins' => Menu::class,
      ];
   }

   if (Config::canAdmin()) {
      $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['dashboardplus'] = 'front/config.form.php';
   }
}

function plugin_version_dashboardplus(): array {
   return [
      'name'         => __('Dashboard Plus', 'dashboardplus'),
      'version'      => PLUGIN_DASHBOARDPLUS_VERSION,
      'author'       => 'Fabio Neres',
      'license'      => 'GPLv3+',
      'homepage'     => '',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION,
            'max' => PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION,
         ],
      ],
   ];
}

function plugin_dashboardplus_check_prerequisites(): bool {
   if (version_compare(GLPI_VERSION, PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION, 'lt')) {
      if (method_exists(Plugin::class, 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION,
            PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION
         );
      } else {
         echo 'Dashboard Plus requer GLPI >= ' . PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION;
      }
      return false;
   }

   if (version_compare(GLPI_VERSION, PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION, 'gt')) {
      if (method_exists(Plugin::class, 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_DASHBOARDPLUS_MIN_GLPI_VERSION,
            PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION
         );
      } else {
         echo 'Dashboard Plus suporta GLPI até ' . PLUGIN_DASHBOARDPLUS_MAX_GLPI_VERSION;
      }
      return false;
   }

   return true;
}

function plugin_dashboardplus_check_config(): bool {
   return true;
}
