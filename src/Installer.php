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
   private const RECOMMENDED_LAYOUT_VERSION = '1.1.4';
   private const LEGACY_DASHBOARD_KEYS = [
      Config::DASHBOARD_OVERVIEW,
      Config::DASHBOARD_ATTENDANCE,
      Config::DASHBOARD_DISTRIBUTIONS,
      Config::DASHBOARD_SLA,
      Config::DASHBOARD_SATISFACTION,
      Config::DASHBOARD_TASKS,
      Config::DASHBOARD_ASSETS,
   ];

   public static function install(): bool
   {
      /** @var DBmysql $DB */
      global $DB;

      try {
         $sqlfile = PLUGIN_DASHBOARDPLUS_DIR . '/install/install.sql';
         if (file_exists($sqlfile)) {
            $DB->runFile($sqlfile);
         }
         self::repairConfigSchema();
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
         self::enableCapacityTabForLegacyFullConfigs();
         WidgetRegistry::ensureDefaultWidgetConfigs();
         self::applyRecommendedLayoutOnce();

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

   private static function repairConfigSchema(): void
   {
      global $DB;

      $table = Config::getConfigTable();
      if (!$DB->tableExists($table)) {
         return;
      }

      $fields = [
         'dashboard_theme' => "ALTER TABLE `$table` ADD `dashboard_theme` varchar(30) NOT NULL DEFAULT 'executive' AFTER `cache_ttl`",
         'accent_color'    => "ALTER TABLE `$table` ADD `accent_color` varchar(7) NOT NULL DEFAULT '#10b6b4' AFTER `dashboard_theme`",
         'chart_palette'   => "ALTER TABLE `$table` ADD `chart_palette` varchar(30) NOT NULL DEFAULT 'business' AFTER `accent_color`",
         'entity_default_enabled' => "ALTER TABLE `$table` ADD `entity_default_enabled` tinyint NOT NULL DEFAULT '1' AFTER `chart_palette`",
         'default_enabled_tabs'   => "ALTER TABLE `$table` ADD `default_enabled_tabs` longtext NULL AFTER `entity_default_enabled`",
         'capacity_enabled'       => "ALTER TABLE `$table` ADD `capacity_enabled` tinyint NOT NULL DEFAULT '1' AFTER `default_enabled_tabs`",
         'capacity_cache_ttl'     => "ALTER TABLE `$table` ADD `capacity_cache_ttl` int unsigned NOT NULL DEFAULT '60' AFTER `capacity_enabled`",
         'capacity_config'        => "ALTER TABLE `$table` ADD `capacity_config` longtext NULL AFTER `capacity_cache_ttl`",
      ];

      foreach ($fields as $field => $sql) {
         if (!$DB->fieldExists($table, $field)) {
            $DB->queryOrDie($sql, 'Dashboard Plus: falha ao atualizar schema de configuração.');
         }
      }

      $entity_table = Config::getEntityConfigTable();
      if (!$DB->tableExists($entity_table)) {
         $DB->queryOrDie(
            "CREATE TABLE IF NOT EXISTS `$entity_table` (
               `id` int unsigned NOT NULL AUTO_INCREMENT,
               `entities_id` int unsigned NOT NULL DEFAULT '0',
               `is_enabled` tinyint NOT NULL DEFAULT '1',
               `is_recursive` tinyint NOT NULL DEFAULT '0',
               `enabled_tabs` longtext NULL,
               `config` longtext NULL,
               `date_creation` timestamp NULL DEFAULT NULL,
               `date_mod` timestamp NULL DEFAULT NULL,
               PRIMARY KEY (`id`),
               UNIQUE KEY `unicity` (`entities_id`),
               KEY `idx_entity_enabled` (`entities_id`, `is_enabled`),
               KEY `idx_recursive` (`is_recursive`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
            'Dashboard Plus: falha ao criar configuração por entidade.'
         );
      }
   }

   private static function enableCapacityTabForLegacyFullConfigs(): void
   {
      global $DB;

      $config_table = Config::getConfigTable();
      if ($DB->tableExists($config_table) && $DB->fieldExists($config_table, 'default_enabled_tabs')) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'default_enabled_tabs'],
            'FROM'   => $config_table,
            'WHERE'  => ['id' => Config::CONFIG_ID],
            'LIMIT'  => 1,
         ]);
         $row = $iterator->current();
         if ($row && self::shouldAppendCapacityTab($row['default_enabled_tabs'] ?? null)) {
            $DB->update($config_table, [
               'default_enabled_tabs' => json_encode(Config::getDashboardKeys()),
               'date_mod'             => date('Y-m-d H:i:s'),
            ], ['id' => (int) $row['id']]);
         }
      }

      $entity_table = Config::getEntityConfigTable();
      if (!$DB->tableExists($entity_table) || !$DB->fieldExists($entity_table, 'enabled_tabs')) {
         return;
      }

      $iterator = $DB->request([
         'SELECT' => ['id', 'enabled_tabs'],
         'FROM'   => $entity_table,
      ]);
      foreach ($iterator as $row) {
         if (!self::shouldAppendCapacityTab($row['enabled_tabs'] ?? null)) {
            continue;
         }

         $tabs = self::decodeTabs($row['enabled_tabs'] ?? null);
         $tabs[] = Config::DASHBOARD_CAPACITY;
         $DB->update($entity_table, [
            'enabled_tabs' => json_encode(Config::normalizeEnabledTabs($tabs)),
            'date_mod'     => date('Y-m-d H:i:s'),
         ], ['id' => (int) $row['id']]);
      }
   }

   private static function shouldAppendCapacityTab($raw): bool
   {
      $tabs = self::decodeTabs($raw);
      if ($tabs === [] || in_array(Config::DASHBOARD_CAPACITY, $tabs, true)) {
         return false;
      }

      return count(array_diff(self::LEGACY_DASHBOARD_KEYS, $tabs)) === 0;
   }

   private static function decodeTabs($raw): array
   {
      if (!is_string($raw) || trim($raw) === '') {
         return [];
      }

      $decoded = json_decode($raw, true);
      if (!is_array($decoded)) {
         return [];
      }

      $tabs = [];
      foreach ($decoded as $key => $value) {
         $tabs[] = is_string($key) && !is_numeric($key) ? $key : (string) $value;
      }

      return array_values(array_unique($tabs));
   }

   private static function applyRecommendedLayoutOnce(): void
   {
      $values = \Config::getConfigurationValues('plugin:dashboardplus', ['recommended_layout_version']);
      if (($values['recommended_layout_version'] ?? '') === self::RECOMMENDED_LAYOUT_VERSION) {
         return;
      }

      WidgetRegistry::applyRecommendedWidgetSizes();

      \Config::setConfigurationValues('plugin:dashboardplus', [
         'recommended_layout_version' => self::RECOMMENDED_LAYOUT_VERSION,
      ]);
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

      \Config::deleteConfigurationValues('plugin:dashboardplus', ['dbversion', 'recommended_layout_version']);
      return true;
   }
}
