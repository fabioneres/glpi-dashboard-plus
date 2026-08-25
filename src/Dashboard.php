<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use CommonDBTM;
use Dropdown;
use Entity;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;
use Group;
use Html;
use ITILCategory;
use Session;
use Ticket;
use User;

class Dashboard extends CommonDBTM
{
   protected static $notable = true;
   public static $rightname = Config::RIGHT_VIEW;

   public static function getTypeName($nb = 0)
   {
      return Config::getTypeName($nb);
   }

   public static function getIcon()
   {
      return Config::getIcon();
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/dashboard.php', $full);
   }

   public static function show(): void
   {
      Config::checkView();

      $settings = Config::getSettings();
      $context = DashboardContext::fromRequest($_GET, $settings);

      if (!Config::canUseDashboardInEntity($context->getEntitiesId(), $context->isRecursive())) {
         echo "<div class='dashboardplus-page'>";
         echo "<div class='dashboardplus-empty-state'>";
         echo "<i class='ti ti-lock'></i>";
         echo "<strong>" . __('Dashboard Plus indisponível para esta entidade', 'dashboardplus') . "</strong>";
         echo "<span>" . __('Verifique a configuração por entidade ou selecione uma entidade permitida.', 'dashboardplus') . "</span>";
         echo "</div>";
         echo "</div>";
         return;
      }

      $widgets = WidgetRegistry::getEnabledWidgets();
      $configs = WidgetRegistry::getWidgetConfigs();
      $refresh = (int) ($settings['auto_refresh'] ?? 0) === 1
         ? max(30, (int) ($settings['refresh_interval'] ?? 300))
         : 0;
      $loading_label = htmlspecialchars(__('Carregando', 'dashboardplus'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $error_label = htmlspecialchars(__('Widget indisponível', 'dashboardplus'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

      $theme_class = Html::cleanInputText(Config::getThemeClass($settings));
      $theme_style = Config::getThemeStyleAttribute($settings);

      echo "<div class='dashboardplus-page {$theme_class}'{$theme_style} data-dashboardplus-refresh='{$refresh}' data-dashboardplus-loading='{$loading_label}' data-dashboardplus-error='{$error_label}'>";
      self::showToolbar($context, $settings);

      if (!count($widgets)) {
         echo "<div class='dashboardplus-empty-state'>";
         echo "<i class='ti ti-layout-dashboard'></i>";
         echo "<strong>" . __('Nenhum widget habilitado', 'dashboardplus') . "</strong>";
         echo "<span>" . __('Habilite widgets nas configurações do Dashboard Plus.', 'dashboardplus') . "</span>";
         echo "</div>";
         echo "</div>";
         return;
      }

      self::showDashboardPanels(self::getDashboardSections($widgets, $context), $configs, $context);

      echo "</div>";
   }

   private static function getDashboardSections(array $widgets, DashboardContext $context): array
   {
      $sections = [];
      $known = [];

      foreach (self::getDashboardDefinitions() as $definition) {
         $known = array_merge($known, $definition['keys']);
         if (!Config::canUseDashboardTabInEntity($definition['dashboard'], $context->getEntitiesId(), $context->isRecursive())) {
            continue;
         }

         $section_widgets = [];
         foreach ($definition['keys'] as $key) {
            if (isset($widgets[$key])) {
               $section_widgets[$key] = $widgets[$key];
            }
         }

         if ($section_widgets !== []) {
            $sections[] = [
               'dashboard'  => $definition['dashboard'],
               'title'      => $definition['title'],
               'widgets'    => $section_widgets,
               'grid_class' => $definition['grid_class'],
            ];
         }
      }

      $others = array_diff_key($widgets, array_flip($known));
      if ($others !== [] && Config::canUseDashboardTabInEntity(Config::DASHBOARD_OVERVIEW, $context->getEntitiesId(), $context->isRecursive())) {
         $sections[] = [
            'dashboard'  => Config::DASHBOARD_OVERVIEW,
            'title'      => __('Indicadores adicionais', 'dashboardplus'),
            'widgets'    => $others,
            'grid_class' => 'dashboardplus-grid',
         ];
      }

      return $sections;
   }

   public static function getWidgetDashboardKey(string $widget_key): string
   {
      foreach (self::getDashboardDefinitions() as $definition) {
         if (in_array($widget_key, $definition['keys'], true)) {
            return $definition['dashboard'];
         }
      }

      return Config::DASHBOARD_OVERVIEW;
   }

   private static function getDashboardDefinitions(): array
   {
      return [
         [
            'dashboard'  => Config::DASHBOARD_ATTENDANCE,
            'title'      => __('Resumo do atendimento', 'dashboardplus'),
            'keys'       => [
               'tickets_new',
               'tickets_unassigned',
               'tickets_planned',
               'notification_queue',
               'tickets_solved',
               'tickets_pending',
               'tickets_closed',
               'tickets_open',
               'tickets_assigned',
               'tickets_solved_today',
               'tickets_priority_medium',
               'tickets_priority_high',
               'tickets_priority_critical',
               'tickets_late',
            ],
            'grid_class' => 'dashboardplus-metrics-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_ATTENDANCE,
            'title'      => __('Evolução diária do atendimento', 'dashboardplus'),
            'keys'       => [
               'tickets_received_by_day',
               'tickets_solved_closed_by_day',
               'tickets_open_by_day',
               'tickets_monthly_evolution',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_ATTENDANCE,
            'title'      => __('Distribuição do atendimento', 'dashboardplus'),
            'keys'       => [
               'tickets_resolution_ratio',
               'tickets_by_entity',
               'tickets_by_status',
               'tickets_by_type',
               'tickets_by_request_type',
               'tickets_by_location',
               'tickets_by_category',
               'tickets_by_group',
               'tickets_by_technician',
               'tickets_by_priority',
               'tickets_pending_reasons',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_SLA,
            'title'      => __('SLA', 'dashboardplus'),
            'keys'       => [
               'sla_compliance',
               'sla_response_compliance',
               'sla_by_technician',
               'sla_by_category',
               'average_solve_time_closed',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_SATISFACTION,
            'title'      => __('Nota de satisfação', 'dashboardplus'),
            'keys'       => [
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
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_TASKS,
            'title'      => __('Tarefas', 'dashboardplus'),
            'keys'       => [
               'task_effort_by_technician',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_ASSETS,
            'title'      => __('Métricas de ativos', 'dashboardplus'),
            'keys'       => [
               'asset_total_computers',
               'asset_total_monitors',
               'asset_total_printers',
               'asset_total_phones',
            ],
            'grid_class' => 'dashboardplus-metrics-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_ASSETS,
            'title'      => __('Distribuição de ativos', 'dashboardplus'),
            'keys'       => [
               'asset_computers_sp_map',
               'asset_computers_by_location',
               'asset_computers_by_manufacturer',
               'asset_monitors_by_manufacturer',
               'asset_computers_by_type',
               'asset_computers_by_os',
               'asset_computers_by_cpu',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_DISTRIBUTIONS,
            'title'      => __('Resumo das distribuições', 'dashboardplus'),
            'keys'       => [
               'distribution_distinct_tickets',
               'distribution_automation_rate',
               'distribution_automation_integral',
               'distribution_automation_partial',
               'distribution_manual_tickets',
               'distribution_transfer_tickets',
            ],
            'grid_class' => 'dashboardplus-metrics-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_DISTRIBUTIONS,
            'title'      => __('Indicadores de distribuição', 'dashboardplus'),
            'keys'       => [
               'distribution_summary_by_distributor',
               'distribution_evolution',
               'distribution_by_category',
               'distribution_top_distributors',
               'distribution_actuation',
               'distribution_top_technicians',
               'distribution_transfers_by_entity',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
         [
            'dashboard'  => Config::DASHBOARD_CAPACITY,
            'title'      => __('Capacidade operacional', 'dashboardplus'),
            'keys'       => [
               'capacity_team_summary',
               'capacity_technician_load',
               'capacity_technician_load_table',
               'capacity_alerts',
            ],
            'grid_class' => 'dashboardplus-grid',
         ],
      ];
   }

   private static function showDashboardPanels(array $sections, array $configs, DashboardContext $context): void
   {
      $dashboards = [
         'overview' => [
            'title' => __('Visão Geral', 'dashboardplus'),
            'icon'  => 'ti ti-activity',
         ],
         'attendance' => [
            'title' => __('Atendimento', 'dashboardplus'),
            'icon'  => 'ti ti-headset',
         ],
         'sla' => [
            'title' => __('SLA', 'dashboardplus'),
            'icon'  => 'ti ti-clock-check',
         ],
         'satisfaction' => [
            'title' => __('Satisfação', 'dashboardplus'),
            'icon'  => 'ti ti-star',
         ],
         'tasks' => [
            'title' => __('Tarefas', 'dashboardplus'),
            'icon'  => 'ti ti-list-check',
         ],
         'assets' => [
            'title' => __('Ativos', 'dashboardplus'),
            'icon'  => 'ti ti-devices',
         ],
         'distributions' => [
            'title' => __('Distribuições', 'dashboardplus'),
            'icon'  => 'ti ti-route',
         ],
         'capacity' => [
            'title' => __('Capacidade', 'dashboardplus'),
            'icon'  => 'ti ti-users-group',
         ],
      ];
      $grouped = [];
      foreach ($sections as $section) {
         $grouped[$section['dashboard']][] = $section;
      }

      echo "<div class='dashboardplus-tabs' role='tablist'>";
      $first = true;
      foreach ($dashboards as $key => $dashboard) {
         if (!isset($grouped[$key])) {
            continue;
         }
         $active = $first ? ' active' : '';
         $selected = $first ? 'true' : 'false';
         $safe_key = Html::cleanInputText($key);
         echo "<button type='button' class='dashboardplus-tab{$active}' data-dashboardplus-tab='{$safe_key}' aria-selected='{$selected}'>";
         echo "<i class='" . Html::cleanInputText($dashboard['icon']) . "'></i>";
         echo "<span>" . Html::cleanInputText($dashboard['title']) . "</span>";
         echo "</button>";
         $first = false;
      }
      echo "</div>";

      $first = true;
      foreach ($dashboards as $key => $dashboard) {
         if (!isset($grouped[$key])) {
            continue;
         }
         $active = $first ? ' active' : '';
         $safe_key = Html::cleanInputText($key);
         echo "<div class='dashboardplus-panel{$active}' data-dashboardplus-panel='{$safe_key}'>";
         foreach ($grouped[$key] as $section) {
            self::showWidgetSection($section['title'], $section['widgets'], $configs, $context, $section['grid_class']);
         }
         echo "</div>";
         $first = false;
      }
   }

   private static function showWidgetSection(string $title, array $widgets, array $configs, DashboardContext $context, string $grid_class): void
   {
      if (!count($widgets)) {
         return;
      }

      echo "<section class='dashboardplus-section'>";
      echo "<h2 class='dashboardplus-section-title'>{$title}</h2>";
      echo "<div class='{$grid_class}'>";
      foreach ($widgets as $key => $widget) {
         self::showWidgetCard($key, $widget, $configs[$key] ?? WidgetRegistry::getWidgetConfig($key), $context);
      }
      echo "</div>";
      echo "</section>";
   }

   private static function showWidgetCard(string $key, $widget, array $config, DashboardContext $context): void
   {
      $default_size = $widget->getDefaultSize();
      $width = min(12, max(1, (int) ($config['width'] ?? ($default_size['width'] ?? 3))));
      $height = min(8, max(1, (int) ($config['height'] ?? ($default_size['height'] ?? 2))));
      $query = http_build_query(['widget' => $key] + $context->toQueryParams());
      $url = Config::pluginUrl('/front/widget.ajax.php') . '?' . $query;
      $safe_key = Html::cleanInputText($key);
      $safe_url = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $title = Html::cleanInputText($widget->getTitle());

      echo "<section class='dashboardplus-card dashboardplus-loading' style='grid-column: span {$width}; grid-row: span {$height}; --dp-widget-rows: {$height};' data-dashboardplus-widget='{$safe_key}' data-url='{$safe_url}'>";
      echo "<div class='dashboardplus-loader'>";
      echo "<i class='ti ti-loader-2'></i>";
      echo "<span>" . sprintf(__('Carregando %s', 'dashboardplus'), $title) . "</span>";
      echo "</div>";
      echo "</section>";
   }

   private static function showToolbar(DashboardContext $context, array $settings): void
   {
      $action = self::getSearchURL();
      $period_days = $context->getPeriodDays();
      $entity_value = $context->getEntitiesId();
      $empty_label = $context->hasConfiguredEntityScope()
         ? __('Entidades configuradas', 'dashboardplus')
         : __('Entidades ativas', 'dashboardplus');
      $has_advanced_filters = $context->getGroupsId() !== null
         || $context->getUsersId() !== null
         || $context->getItilcategoriesId() !== null
         || $context->getType() !== null
         || $context->getPriority() !== null
         || $context->hasCustomSatisfactionPeriod();
      $advanced_class = $has_advanced_filters ? ' is-open' : '';
      $advanced_expanded = $has_advanced_filters ? 'true' : 'false';

      echo "<div class='dashboardplus-toolbar'>";
      echo "<div>";
      echo "<h1><i class='" . self::getIcon() . "'></i> " . self::getTypeName() . "</h1>";
      echo "<p>" . __('Indicadores operacionais, táticos e executivos sem duplicar dados do GLPI.', 'dashboardplus') . "</p>";
      echo "</div>";

      echo "<form method='get' action='{$action}' class='dashboardplus-filters'>";
      echo "<div class='dashboardplus-filter-row dashboardplus-filter-main'>";

      echo "<label>";
      echo "<span>" . __('Período', 'dashboardplus') . "</span>";
      echo "<select name='period_days' class='form-select'>";
      $selected = $period_days === 0 ? " selected" : "";
      echo "<option value='0'{$selected}>" . __('Todo histórico', 'dashboardplus') . "</option>";
      foreach ([7, 15, 30, 60, 90, 180, 365] as $days) {
         $selected = $period_days === $days ? " selected" : "";
         echo "<option value='{$days}'{$selected}>{$days} " . __('dias', 'dashboardplus') . "</option>";
      }
      echo "</select>";
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Início', 'dashboardplus') . "</span>";
      echo "<input type='date' class='form-control' name='start' value='" . Html::cleanInputText($context->getStart()) . "'>";
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Fim', 'dashboardplus') . "</span>";
      echo "<input type='date' class='form-control' name='end' value='" . Html::cleanInputText($context->getEnd()) . "'>";
      echo "</label>";

      echo "<label class='dashboardplus-entity-filter'>";
      echo "<span>" . __('Entidade', 'dashboardplus') . "</span>";
      Dropdown::showFromArray('entities_id', self::getAvailableEntities(), [
         'value'               => $entity_value,
         'width'               => '100%',
         'display_emptychoice' => true,
         'emptylabel'          => $empty_label,
      ]);
      echo "</label>";

      echo "<label class='dashboardplus-check'>";
      $checked = $context->isRecursive() ? " checked" : "";
      echo "<input type='checkbox' name='is_recursive' value='1'{$checked}>";
      echo "<span>" . __('Recursivo', 'dashboardplus') . "</span>";
      echo "</label>";

      echo "<button type='button' class='btn btn-outline-secondary dashboardplus-advanced-toggle' aria-expanded='{$advanced_expanded}'>";
      echo "<i class='ti ti-adjustments-horizontal'></i> " . __('Filtros avançados', 'dashboardplus');
      echo "</button>";

      echo Html::submit(__('Aplicar', 'dashboardplus'), [
         'class' => 'btn btn-primary',
         'icon'  => 'ti ti-filter',
      ]);

      echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/about.php') . "'>";
      echo "<i class='ti ti-info-circle'></i> " . __('Sobre', 'dashboardplus');
      echo "</a>";

      if (Config::canAdmin()) {
         echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/config.form.php') . "'>";
         echo "<i class='ti ti-settings'></i> " . __('Configurações', 'dashboardplus');
         echo "</a>";
      }

      echo "</div>";
      echo "<div class='dashboardplus-filter-row dashboardplus-filter-advanced{$advanced_class}'>";

      echo "<label>";
      echo "<span>" . __('Grupo técnico', 'dashboardplus') . "</span>";
      Dropdown::show(Group::class, [
         'name'                => 'groups_id',
         'value'               => $context->getGroupsId(),
         'width'               => '100%',
         'display_emptychoice' => true,
      ]);
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Técnico', 'dashboardplus') . "</span>";
      User::dropdown([
         'name'                => 'users_id',
         'value'               => $context->getUsersId(),
         'right'               => 'all',
         'width'               => '100%',
         'display_emptychoice' => true,
      ]);
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Categoria', 'dashboardplus') . "</span>";
      Dropdown::show(ITILCategory::class, [
         'name'                => 'itilcategories_id',
         'value'               => $context->getItilcategoriesId(),
         'width'               => '100%',
         'display_emptychoice' => true,
      ]);
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Tipo', 'dashboardplus') . "</span>";
      echo "<select name='type' class='form-select'>";
      echo "<option value=''>" . __('Todos', 'dashboardplus') . "</option>";
      foreach ([Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE] as $type) {
         $selected = $context->getType() === $type ? " selected" : "";
         echo "<option value='{$type}'{$selected}>" . Html::cleanInputText(Ticket::getTicketTypeName($type)) . "</option>";
      }
      echo "</select>";
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Prioridade', 'dashboardplus') . "</span>";
      echo "<select name='priority' class='form-select'>";
      echo "<option value=''>" . __('Todas', 'dashboardplus') . "</option>";
      for ($priority = 1; $priority <= 6; $priority++) {
         $selected = $context->getPriority() === $priority ? " selected" : "";
         echo "<option value='{$priority}'{$selected}>" . Html::cleanInputText(Ticket::getPriorityName($priority)) . "</option>";
      }
      echo "</select>";
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Data pesquisa início', 'dashboardplus') . "</span>";
      echo "<input type='date' class='form-control' name='satisfaction_start' value='" . Html::cleanInputText($context->getSatisfactionStart()) . "'>";
      echo "</label>";

      echo "<label>";
      echo "<span>" . __('Data pesquisa fim', 'dashboardplus') . "</span>";
      echo "<input type='date' class='form-control' name='satisfaction_end' value='" . Html::cleanInputText($context->getSatisfactionEnd()) . "'>";
      echo "</label>";

      echo "</div>";
      echo "</form>";
      echo "</div>";
   }

   private static function getAvailableEntities(): array
   {
      global $DB;

      $entities = [];
      foreach ($DB->request([
         'FROM'  => Entity::getTable(),
         'ORDER' => ['completename ASC', 'name ASC'],
      ]) as $row) {
         $entities_id = (int) ($row['id'] ?? 0);
         if (!Session::haveAccessToEntity($entities_id, true)) {
            continue;
         }

         $label = (string) ($row['completename'] ?? '');
         if ($label === '') {
            $label = (string) ($row['name'] ?? '');
         }
         if ($label === '') {
            $label = Dropdown::getDropdownName(Entity::getTable(), $entities_id);
         }

         $entities[$entities_id] = $label;
      }

      return $entities;
   }
}
