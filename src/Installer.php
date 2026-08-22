<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use DBmysql;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;
use Session;
use Throwable;
use Toolbox;

class Installer
{
   public static function install(): bool
   {
      /** @var DBmysql $DB */
      global $DB;

      try {
         $sqlfile = PLUGIN_DASHBOARDPLUS_DIR . '/install/install.sql';
         if (file_exists($sqlfile)) {
            $DB->runFile($sqlfile);
         }
      } catch (Throwable $e) {
         Toolbox::logInFile(
            'plugin_dashboardplus',
            'Falha na instalação: ' . $e->getMessage() . PHP_EOL
         );
         Session::addMessageAfterRedirect(
            __('Dashboard Plus: falha ao criar as tabelas do banco de dados. Verifique os logs do GLPI.', 'dashboardplus'),
            false,
            ERROR
         );
         return false;
      }

      try {
         Profile::installRights();
         Profile::initProfile();
         Config::ensureDefaultConfig();
         WidgetRegistry::ensureDefaultWidgetConfigs();

         if (isset($_SESSION['glpiactiveprofile']['id'])) {
            Profile::createFirstAccess((int) $_SESSION['glpiactiveprofile']['id']);
         }

         \Config::setConfigurationValues('plugin:dashboardplus', [
            'dbversion' => PLUGIN_DASHBOARDPLUS_SCHEMA_VERSION,
         ]);
      } catch (Throwable $e) {
         Logger::exception($e, 'Falha na etapa final da instalação');
         return false;
      }

      return true;
   }

   public static function uninstall(): bool
   {
      /** @var DBmysql $DB */
      global $DB;

      try {
         $sqlfile = PLUGIN_DASHBOARDPLUS_DIR . '/install/uninstall.sql';
         if (file_exists($sqlfile)) {
            $DB->runFile($sqlfile);
         }
      } catch (Throwable $e) {
         Logger::exception($e, 'Falha na desinstalação');
         return false;
      }

      \Config::deleteConfigurationValues('plugin:dashboardplus', ['dbversion']);
      return true;
   }
}
