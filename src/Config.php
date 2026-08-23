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
   public const RIGHT_TAB_OVERVIEW      = 'plugin_dashboardplus_tab_overview';
   public const RIGHT_TAB_ATTENDANCE    = 'plugin_dashboardplus_tab_attendance';
   public const RIGHT_TAB_SLA           = 'plugin_dashboardplus_tab_sla';
   public const RIGHT_TAB_SATISFACTION  = 'plugin_dashboardplus_tab_satisfaction';
   public const RIGHT_TAB_TASKS         = 'plugin_dashboardplus_tab_tasks';
   public const RIGHT_TAB_ASSETS        = 'plugin_dashboardplus_tab_assets';
   public const RIGHT_TAB_DISTRIBUTIONS = 'plugin_dashboardplus_tab_distributions';
   public const RIGHT_TAB_CAPACITY      = 'plugin_dashboardplus_tab_capacity';

   public const DASHBOARD_OVERVIEW      = 'overview';
   public const DASHBOARD_ATTENDANCE    = 'attendance';
   public const DASHBOARD_SLA           = 'sla';
   public const DASHBOARD_SATISFACTION  = 'satisfaction';
   public const DASHBOARD_TASKS         = 'tasks';
   public const DASHBOARD_ASSETS        = 'assets';
   public const DASHBOARD_DISTRIBUTIONS = 'distributions';
   public const DASHBOARD_CAPACITY      = 'capacity';

   private const DEFAULT_PRIORITY_WEIGHTS = [
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
   ];

   private const DEFAULT_AGING_WEIGHTS = [
      ['min' => 0,  'max' => 2,  'weight' => 0],
      ['min' => 3,  'max' => 7,  'weight' => 1],
      ['min' => 8,  'max' => 15, 'weight' => 2],
      ['min' => 16, 'max' => 30, 'weight' => 3],
      ['min' => 31, 'max' => 0,  'weight' => 4],
   ];

   private const DEFAULT_SLA_WEIGHTS = [
      'comfortable' => 0,
      'attention'   => 1,
      'critical'    => 3,
      'violated'    => 5,
   ];

   private const DEFAULT_LOAD_THRESHOLDS = [
      'low'      => 25,
      'moderate' => 50,
      'high'     => 75,
   ];

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
         self::RIGHT_TAB_OVERVIEW,
         self::RIGHT_TAB_ATTENDANCE,
         self::RIGHT_TAB_SLA,
         self::RIGHT_TAB_SATISFACTION,
         self::RIGHT_TAB_TASKS,
         self::RIGHT_TAB_ASSETS,
         self::RIGHT_TAB_DISTRIBUTIONS,
         self::RIGHT_TAB_CAPACITY,
      ];
   }

   public static function getDashboardTabRights(): array
   {
      return [
         self::DASHBOARD_OVERVIEW      => self::RIGHT_TAB_OVERVIEW,
         self::DASHBOARD_ATTENDANCE    => self::RIGHT_TAB_ATTENDANCE,
         self::DASHBOARD_SLA           => self::RIGHT_TAB_SLA,
         self::DASHBOARD_SATISFACTION  => self::RIGHT_TAB_SATISFACTION,
         self::DASHBOARD_TASKS         => self::RIGHT_TAB_TASKS,
         self::DASHBOARD_ASSETS        => self::RIGHT_TAB_ASSETS,
         self::DASHBOARD_DISTRIBUTIONS => self::RIGHT_TAB_DISTRIBUTIONS,
         self::DASHBOARD_CAPACITY      => self::RIGHT_TAB_CAPACITY,
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

   public static function getEntityConfigTable(): string
   {
      return 'glpi_plugin_dashboardplus_entityconfigs';
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

   public static function canViewDashboardTab(string $dashboard): bool
   {
      if (!self::canView()) {
         return false;
      }

      if (self::canAdmin()) {
         return true;
      }

      $rights = self::getDashboardTabRights();
      $right = $rights[$dashboard] ?? self::RIGHT_TAB_OVERVIEW;

      return Session::haveRight($right, READ);
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
         'dashboard_theme'     => 'executive',
         'accent_color'        => '#10b6b4',
         'chart_palette'       => 'business',
         'entity_default_enabled' => 1,
         'default_enabled_tabs'   => self::getDashboardKeys(),
         'capacity_enabled'       => 1,
         'capacity_cache_ttl'     => 60,
         'capacity_config'        => self::getDefaultCapacityConfig(),
      ];
   }

   public static function getDashboardLabels(): array
   {
      return [
         self::DASHBOARD_OVERVIEW      => __('Visão Geral', 'dashboardplus'),
         self::DASHBOARD_ATTENDANCE    => __('Atendimento', 'dashboardplus'),
         self::DASHBOARD_DISTRIBUTIONS => __('Distribuição', 'dashboardplus'),
         self::DASHBOARD_CAPACITY      => __('Capacidade', 'dashboardplus'),
         self::DASHBOARD_SLA           => __('SLA', 'dashboardplus'),
         self::DASHBOARD_SATISFACTION  => __('Satisfação', 'dashboardplus'),
         self::DASHBOARD_TASKS         => __('Tarefas', 'dashboardplus'),
         self::DASHBOARD_ASSETS        => __('Ativos', 'dashboardplus'),
      ];
   }

   public static function getDashboardKeys(): array
   {
      return array_keys(self::getDashboardLabels());
   }

   public static function getDefaultCapacityConfig(): array
   {
      return [
         'source'                    => 'auto',
         'standard_weekly_hours'     => 40,
         'priority_weights'          => self::DEFAULT_PRIORITY_WEIGHTS,
         'aging_weights'             => self::DEFAULT_AGING_WEIGHTS,
         'sla_weights'               => self::DEFAULT_SLA_WEIGHTS,
         'load_thresholds'           => self::DEFAULT_LOAD_THRESHOLDS,
         'sla_attention_percent'     => 70,
         'sla_critical_percent'      => 90,
      ];
   }

   public static function getThemeOptions(): array
   {
      return [
         'executive' => __('Executivo claro', 'dashboardplus'),
         'ocean'     => __('Azul e verde', 'dashboardplus'),
         'graphite'  => __('Grafite executivo', 'dashboardplus'),
         'metabase'  => __('Metabase escuro', 'dashboardplus'),
      ];
   }

   public static function getPaletteOptions(): array
   {
      return [
         'business' => __('Executiva colorida', 'dashboardplus'),
         'vivid'    => __('Viva para apresentações', 'dashboardplus'),
         'calm'     => __('Suave corporativa', 'dashboardplus'),
         'metabase' => __('Metabase clássico', 'dashboardplus'),
      ];
   }

   public static function getPaletteColors(?string $palette = null): array
   {
      $palette = self::normalizePalette((string) ($palette ?? self::getSettings()['chart_palette']));
      $palettes = [
         'business' => ['#10b6b4', '#2563eb', '#f59e0b', '#ef4444', '#8b5cf6', '#22c55e', '#ec4899', '#0ea5e9', '#f97316', '#64748b'],
         'vivid'    => ['#06b6d4', '#7c3aed', '#facc15', '#fb7185', '#22c55e', '#3b82f6', '#f97316', '#14b8a6', '#e879f9', '#84cc16'],
         'calm'     => ['#14b8a6', '#60a5fa', '#fbbf24', '#f87171', '#a78bfa', '#86efac', '#38bdf8', '#fb923c', '#94a3b8', '#0f766e'],
         'metabase' => ['#84cc16', '#2563eb', '#f97316', '#ef4444', '#22c55e', '#eab308', '#06b6d4', '#a855f7', '#f43f5e', '#64748b'],
      ];

      return $palettes[$palette];
   }

   public static function getThemeClass(array $settings): string
   {
      return 'dashboardplus-theme-' . self::normalizeTheme((string) ($settings['dashboard_theme'] ?? 'executive'));
   }

   public static function getThemeStyleAttribute(array $settings): string
   {
      $accent = self::normalizeColor((string) ($settings['accent_color'] ?? '#10b6b4'), '#10b6b4');
      return " style='--dp-accent: " . Html::cleanInputText($accent) . "'";
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

      $defaults = self::getDefaultSettings();
      $defaults['default_enabled_tabs'] = json_encode($defaults['default_enabled_tabs']);
      $defaults['capacity_config'] = json_encode($defaults['capacity_config']);

      $DB->insert(self::getConfigTable(), $defaults + [
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
         'dashboard_theme'     => self::normalizeTheme((string) ($row['dashboard_theme'] ?? $defaults['dashboard_theme'])),
         'accent_color'        => self::normalizeColor((string) ($row['accent_color'] ?? $defaults['accent_color']), $defaults['accent_color']),
         'chart_palette'       => self::normalizePalette((string) ($row['chart_palette'] ?? $defaults['chart_palette'])),
         'entity_default_enabled' => (int) ($row['entity_default_enabled'] ?? $defaults['entity_default_enabled']),
         'default_enabled_tabs'   => self::normalizeEnabledTabs(self::decodeJson($row['default_enabled_tabs'] ?? null, $defaults['default_enabled_tabs'])),
         'capacity_enabled'       => (int) ($row['capacity_enabled'] ?? $defaults['capacity_enabled']),
         'capacity_cache_ttl'     => max(30, (int) ($row['capacity_cache_ttl'] ?? $defaults['capacity_cache_ttl'])),
         'capacity_config'        => self::normalizeCapacityConfig(self::decodeJson($row['capacity_config'] ?? null, $defaults['capacity_config'])),
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
         'dashboard_theme'     => self::normalizeTheme((string) ($input['dashboard_theme'] ?? 'executive')),
         'accent_color'        => self::normalizeColor((string) ($input['accent_color'] ?? '#10b6b4'), '#10b6b4'),
         'chart_palette'       => self::normalizePalette((string) ($input['chart_palette'] ?? 'business')),
         'entity_default_enabled' => (int) ($input['entity_default_enabled'] ?? 0) === 1 ? 1 : 0,
         'default_enabled_tabs'   => json_encode(self::normalizeEnabledTabs($input['default_enabled_tabs'] ?? [])),
         'capacity_enabled'       => (int) ($input['capacity_enabled'] ?? 0) === 1 ? 1 : 0,
         'capacity_cache_ttl'     => min(3600, max(30, (int) ($input['capacity_cache_ttl'] ?? 60))),
         'capacity_config'        => json_encode(self::capacityConfigFromInput($input)),
         'date_mod'            => date('Y-m-d H:i:s'),
      ];

      $DB->update(self::getConfigTable(), $payload, ['id' => self::CONFIG_ID]);
   }

   private static function normalizeTheme(string $theme): string
   {
      return array_key_exists($theme, self::getThemeOptions()) ? $theme : 'executive';
   }

   private static function normalizePalette(string $palette): string
   {
      return array_key_exists($palette, self::getPaletteOptions()) ? $palette : 'business';
   }

   private static function normalizeColor(string $color, string $fallback): string
   {
      $color = trim($color);
      return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $fallback;
   }

   public static function getEffectiveEntityConfig(?int $entities_id): array
   {
      global $DB;

      $settings = self::getSettings();
      $fallback = [
         'source'       => 'global',
         'entities_id'  => $entities_id,
         'is_enabled'   => (int) ($settings['entity_default_enabled'] ?? 0),
         'is_recursive' => 1,
         'enabled_tabs' => self::normalizeEnabledTabs($settings['default_enabled_tabs'] ?? []),
         'config'       => [],
      ];

      if ($entities_id === null) {
         $active = (int) ($_SESSION['glpiactive_entity'] ?? 0);
         $entities_id = $active >= 0 ? $active : 0;
      }

      if (!$DB->tableExists(self::getEntityConfigTable())) {
         return $fallback;
      }

      $explicit = self::getEntityConfigRow((int) $entities_id);
      if ($explicit !== null) {
         return self::formatEntityConfig($explicit, 'explicit');
      }

      $ancestors = function_exists('getAncestorsOf') ? array_values(getAncestorsOf('glpi_entities', (int) $entities_id)) : [];
      $ancestors = array_reverse(array_map('intval', $ancestors));
      foreach ($ancestors as $ancestor_id) {
         $row = self::getEntityConfigRow($ancestor_id);
         if ($row !== null && (int) ($row['is_recursive'] ?? 0) === 1) {
            return self::formatEntityConfig($row, 'inherited');
         }
      }

      return $fallback;
   }

   public static function canUseDashboardInEntity(?int $entities_id, bool $recursive = false): bool
   {
      $target = $entities_id;
      if ($target === null) {
         $target = (int) ($_SESSION['glpiactive_entity'] ?? 0);
      }

      if ($target < 0 || !Session::haveAccessToEntity((int) $target, $recursive)) {
         return false;
      }

      $config = self::getEffectiveEntityConfig((int) $target);
      return (int) ($config['is_enabled'] ?? 0) === 1;
   }

   public static function canUseDashboardTabInEntity(string $dashboard, ?int $entities_id, bool $recursive = false): bool
   {
      if (!self::canViewDashboardTab($dashboard)) {
         return false;
      }

      $target = $entities_id;
      if ($target === null) {
         $target = (int) ($_SESSION['glpiactive_entity'] ?? 0);
      }

      if ($target < 0 || !Session::haveAccessToEntity((int) $target, $recursive)) {
         return false;
      }

      $config = self::getEffectiveEntityConfig((int) $target);
      if ((int) ($config['is_enabled'] ?? 0) !== 1) {
         return false;
      }

      return in_array($dashboard, self::normalizeEnabledTabs($config['enabled_tabs'] ?? []), true);
   }

   public static function getEntityConfigRows(): array
   {
      global $DB;

      if (!$DB->tableExists(self::getEntityConfigTable())) {
         return [];
      }

      $rows = [];
      $iterator = $DB->request([
         'FROM'  => self::getEntityConfigTable(),
         'ORDER' => ['entities_id ASC'],
      ]);

      foreach ($iterator as $row) {
         $rows[] = self::formatEntityConfig($row, 'explicit');
      }

      return $rows;
   }

   public static function saveEntityAvailability(array $input): void
   {
      global $DB;

      self::checkAdmin();
      if (!$DB->tableExists(self::getEntityConfigTable())) {
         return;
      }

      $entities = $input['entity_config'] ?? [];
      if (!is_array($entities)) {
         $entities = [];
      }

      foreach ($entities as $raw_entity_id => $row) {
         if (!is_array($row)) {
            continue;
         }

         $entities_id = max(0, (int) $raw_entity_id);
         if (!Session::haveAccessToEntity($entities_id, true)) {
            continue;
         }

         $enabled_tabs = self::normalizeEnabledTabs($row['tabs'] ?? []);
         $payload = [
            'entities_id'    => $entities_id,
            'is_enabled'     => (int) ($row['is_enabled'] ?? 0) === 1 ? 1 : 0,
            'is_recursive'   => (int) ($row['is_recursive'] ?? 0) === 1 ? 1 : 0,
            'enabled_tabs'   => json_encode($enabled_tabs),
            'config'         => json_encode([]),
            'date_mod'       => date('Y-m-d H:i:s'),
         ];

         $current = self::getEntityConfigRow($entities_id);
         if ($current) {
            $DB->update(self::getEntityConfigTable(), $payload, ['id' => (int) $current['id']]);
         } else {
            $payload['date_creation'] = date('Y-m-d H:i:s');
            $DB->insert(self::getEntityConfigTable(), $payload);
         }
      }

      $new = $input['new_entity_config'] ?? [];
      if (is_array($new) && isset($new['entities_id']) && $new['entities_id'] !== '') {
         $entities_id = max(0, (int) $new['entities_id']);
         if (Session::haveAccessToEntity($entities_id, true)) {
            $enabled_tabs = self::normalizeEnabledTabs($new['tabs'] ?? self::getDashboardKeys());
            $current = self::getEntityConfigRow($entities_id);
            $payload = [
               'entities_id'    => $entities_id,
               'is_enabled'     => (int) ($new['is_enabled'] ?? 1) === 1 ? 1 : 0,
               'is_recursive'   => (int) ($new['is_recursive'] ?? 0) === 1 ? 1 : 0,
               'enabled_tabs'   => json_encode($enabled_tabs),
               'config'         => json_encode([]),
               'date_mod'       => date('Y-m-d H:i:s'),
            ];
            if ($current) {
               $DB->update(self::getEntityConfigTable(), $payload, ['id' => (int) $current['id']]);
            } else {
               $payload['date_creation'] = date('Y-m-d H:i:s');
               $DB->insert(self::getEntityConfigTable(), $payload);
            }
         }
      }
   }

   public static function normalizeEnabledTabs($tabs): array
   {
      if (!is_array($tabs)) {
         return [];
      }

      $allowed = self::getDashboardKeys();
      $normalized = [];
      foreach ($tabs as $key => $value) {
         $tab = is_string($key) && !is_numeric($key) ? $key : (string) $value;
         if (in_array($tab, $allowed, true)) {
            $normalized[] = $tab;
         }
      }

      return array_values(array_unique($normalized));
   }

   public static function normalizeCapacityConfig(array $config): array
   {
      $defaults = self::getDefaultCapacityConfig();
      $config = array_replace_recursive($defaults, $config);
      $config['standard_weekly_hours'] = max(1, min(80, (int) ($config['standard_weekly_hours'] ?? 40)));
      $config['sla_attention_percent'] = max(1, min(99, (int) ($config['sla_attention_percent'] ?? 70)));
      $config['sla_critical_percent'] = max($config['sla_attention_percent'], min(100, (int) ($config['sla_critical_percent'] ?? 90)));

      foreach (range(1, 6) as $priority) {
         $config['priority_weights'][$priority] = max(0, min(20, (int) ($config['priority_weights'][$priority] ?? $priority)));
      }
      foreach (['comfortable', 'attention', 'critical', 'violated'] as $key) {
         $config['sla_weights'][$key] = max(0, min(20, (int) ($config['sla_weights'][$key] ?? $defaults['sla_weights'][$key])));
      }
      foreach (['low', 'moderate', 'high'] as $key) {
         $config['load_thresholds'][$key] = max(0, min(999, (int) ($config['load_thresholds'][$key] ?? $defaults['load_thresholds'][$key])));
      }

      return $config;
   }

   private static function capacityConfigFromInput(array $input): array
   {
      $config = self::getDefaultCapacityConfig();
      $config['standard_weekly_hours'] = (int) ($input['capacity_standard_weekly_hours'] ?? 40);
      $config['sla_attention_percent'] = (int) ($input['capacity_sla_attention_percent'] ?? 70);
      $config['sla_critical_percent'] = (int) ($input['capacity_sla_critical_percent'] ?? 90);

      foreach (range(1, 6) as $priority) {
         $config['priority_weights'][$priority] = (int) ($input['capacity_priority_weights'][$priority] ?? $priority);
      }
      foreach (['comfortable', 'attention', 'critical', 'violated'] as $key) {
         $config['sla_weights'][$key] = (int) ($input['capacity_sla_weights'][$key] ?? $config['sla_weights'][$key]);
      }
      foreach (['low', 'moderate', 'high'] as $key) {
         $config['load_thresholds'][$key] = (int) ($input['capacity_load_thresholds'][$key] ?? $config['load_thresholds'][$key]);
      }

      return self::normalizeCapacityConfig($config);
   }

   private static function getEntityConfigRow(int $entities_id): ?array
   {
      global $DB;

      if (!$DB->tableExists(self::getEntityConfigTable())) {
         return null;
      }

      $iterator = $DB->request([
         'FROM'  => self::getEntityConfigTable(),
         'WHERE' => ['entities_id' => $entities_id],
         'LIMIT' => 1,
      ]);

      $row = $iterator->current();
      return $row ? (array) $row : null;
   }

   private static function formatEntityConfig(array $row, string $source): array
   {
      return [
         'source'       => $source,
         'id'           => (int) ($row['id'] ?? 0),
         'entities_id'  => (int) ($row['entities_id'] ?? 0),
         'is_enabled'   => (int) ($row['is_enabled'] ?? 0),
         'is_recursive' => (int) ($row['is_recursive'] ?? 0),
         'enabled_tabs' => self::normalizeEnabledTabs(self::decodeJson($row['enabled_tabs'] ?? null, [])),
         'config'       => self::decodeJson($row['config'] ?? null, []),
      ];
   }

   private static function decodeJson($raw, array $fallback): array
   {
      if (is_array($raw)) {
         return $raw;
      }
      if (!is_string($raw) || trim($raw) === '') {
         return $fallback;
      }

      $decoded = json_decode($raw, true);
      return is_array($decoded) ? $decoded : $fallback;
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
