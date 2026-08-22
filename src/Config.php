<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use CommonDBTM;
use Html;
use Session;

class Config extends CommonDBTM
{
   protected static $notable = true;

   public const CONFIG_ID = 1;

   public const RIGHT_VIEW    = 'plugin_dashboardplus_view';
   public const RIGHT_ADMIN   = 'plugin_dashboardplus_admin';
   public const RIGHT_WIDGETS = 'plugin_dashboardplus_widgets';
   public const RIGHT_GLOBAL  = 'plugin_dashboardplus_global';

   public static function getTypeName($nb = 0)
   {
      return __('Dashboard Plus', 'dashboardplus');
   }

   public static function getIcon()
   {
      return 'ti ti-layout-dashboard';
   }

   public static function getRightNames(): array
   {
      return [
         self::RIGHT_VIEW,
         self::RIGHT_ADMIN,
         self::RIGHT_WIDGETS,
         self::RIGHT_GLOBAL,
      ];
   }

   public static function getConfigTable(): string
   {
      return 'glpi_plugin_dashboardplus_configs';
   }

   public static function getConfigEntitiesTable(): string
   {
      return 'glpi_plugin_dashboardplus_configentities';
   }

   public static function getWidgetConfigTable(): string
   {
      return 'glpi_plugin_dashboardplus_widgetconfigs';
   }

   public static function canView(): bool
   {
      return Session::haveRight(self::RIGHT_VIEW, READ)
         || self::canAdmin();
   }

   public static function canAdmin(): bool
   {
      return Session::haveRight(self::RIGHT_ADMIN, UPDATE)
         || Session::haveRight(\Config::$rightname, UPDATE);
   }

   public static function canConfigureWidgets(): bool
   {
      return Session::haveRight(self::RIGHT_WIDGETS, UPDATE)
         || self::canAdmin();
   }

   public static function canViewGlobalIndicators(): bool
   {
      return Session::haveRight(self::RIGHT_GLOBAL, READ)
         || self::canAdmin();
   }

   public static function checkView(): void
   {
      Session::checkLoginUser();
      if (!self::canView()) {
         Html::displayRightError();
      }
   }

   public static function checkAdmin(): void
   {
      Session::checkLoginUser();
      if (!self::canAdmin()) {
         Html::displayRightError();
      }
   }

   public static function checkConfigureWidgets(): void
   {
      Session::checkLoginUser();
      if (!self::canConfigureWidgets()) {
         Html::displayRightError();
      }
   }

   public static function pluginUrl(string $path = '', bool $full = true): string
   {
      global $CFG_GLPI;

      $relative = '/plugins/dashboardplus';
      $base = $full
         ? rtrim((string) ($CFG_GLPI['root_doc'] ?? ''), '/') . $relative
         : $relative;

      if ($path === '') {
         return $base;
      }

      return $base . '/' . ltrim($path, '/');
   }

   public static function getDefaultSettings(): array
   {
      return [
         'default_period_days' => 30,
         'auto_refresh'        => 1,
         'refresh_interval'    => 300,
         'use_cache'           => 1,
         'cache_ttl'           => 120,
      ];
   }

   public static function ensureDefaultConfig(): void
   {
      global $DB;

      if (!$DB->tableExists(self::getConfigTable())) {
         return;
      }

      $iterator = $DB->request([
         'FROM'  => self::getConfigTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ]);

      if ($iterator->current()) {
         return;
      }

      $DB->insert(self::getConfigTable(), self::getDefaultSettings() + [
         'id'            => self::CONFIG_ID,
         'date_creation' => date('Y-m-d H:i:s'),
         'date_mod'      => date('Y-m-d H:i:s'),
      ]);
   }

   public static function getSettings(): array
   {
      global $DB;

      self::ensureDefaultConfig();
      $defaults = self::getDefaultSettings();

      if (!$DB->tableExists(self::getConfigTable())) {
         return $defaults;
      }

      $iterator = $DB->request([
         'FROM'  => self::getConfigTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ]);
      $row = $iterator->current();

      if (!$row) {
         return $defaults;
      }

      return [
         'default_period_days' => max(1, (int) ($row['default_period_days'] ?? $defaults['default_period_days'])),
         'auto_refresh'        => (int) ($row['auto_refresh'] ?? $defaults['auto_refresh']),
         'refresh_interval'    => max(30, (int) ($row['refresh_interval'] ?? $defaults['refresh_interval'])),
         'use_cache'           => (int) ($row['use_cache'] ?? $defaults['use_cache']),
         'cache_ttl'           => max(30, (int) ($row['cache_ttl'] ?? $defaults['cache_ttl'])),
      ];
   }

   public static function saveSettings(array $input): void
   {
      global $DB;

      self::checkAdmin();
      self::ensureDefaultConfig();

      $payload = [
         'default_period_days' => min(366, max(1, (int) ($input['default_period_days'] ?? 30))),
         'auto_refresh'        => (int) ($input['auto_refresh'] ?? 0),
         'refresh_interval'    => min(3600, max(30, (int) ($input['refresh_interval'] ?? 300))),
         'use_cache'           => (int) ($input['use_cache'] ?? 0),
         'cache_ttl'           => min(3600, max(30, (int) ($input['cache_ttl'] ?? 120))),
         'date_mod'            => date('Y-m-d H:i:s'),
      ];

      $DB->update(self::getConfigTable(), $payload, ['id' => self::CONFIG_ID]);
   }

   public static function getConfiguredEntityRows(): array
   {
      global $DB;

      if (!$DB->tableExists(self::getConfigEntitiesTable())) {
         return [];
      }

      $rows = [];
      $iterator = $DB->request([
         'FROM'  => self::getConfigEntitiesTable(),
         'WHERE' => ['plugin_dashboardplus_configs_id' => self::CONFIG_ID],
         'ORDER' => ['entities_id ASC'],
      ]);

      foreach ($iterator as $row) {
         $rows[] = [
            'entities_id'  => (int) $row['entities_id'],
            'is_recursive' => (int) ($row['is_recursive'] ?? 1),
         ];
      }

      return $rows;
   }

   public static function getConfiguredEntityIds(): array
   {
      return array_map(static function(array $row): int {
         return (int) $row['entities_id'];
      }, self::getConfiguredEntityRows());
   }

   public static function saveEntityScope(array $input): void
   {
      global $DB;

      self::checkAdmin();
      if (!$DB->tableExists(self::getConfigEntitiesTable())) {
         return;
      }

      $entities = $input['dashboardplus_entities_id'] ?? [];
      if (!is_array($entities)) {
         $entities = [$entities];
      }

      $entities = array_values(array_unique(array_filter(array_map('intval', $entities), static function(int $id): bool {
         return $id >= 0 && Session::haveAccessToEntity($id, true);
      })));
      $recursive = (int) ($input['dashboardplus_entities_recursive'] ?? 1) === 1 ? 1 : 0;

      $DB->delete(self::getConfigEntitiesTable(), [
         'plugin_dashboardplus_configs_id' => self::CONFIG_ID,
      ]);

      foreach ($entities as $entities_id) {
         $DB->insert(self::getConfigEntitiesTable(), [
            'plugin_dashboardplus_configs_id' => self::CONFIG_ID,
            'entities_id'                     => $entities_id,
            'is_recursive'                    => $recursive,
         ]);
      }
   }
}
