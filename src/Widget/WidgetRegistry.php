<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Logger;
use Throwable;

class WidgetRegistry
{
   private static $extra_widget_classes = [];

   public const VISUALIZATION_CARD    = 'card';
   public const VISUALIZATION_COMPACT = 'compact';
   public const VISUALIZATION_BARS    = 'bars';
   public const VISUALIZATION_COLUMNS = 'columns';
   public const VISUALIZATION_TABLE   = 'table';
   public const VISUALIZATION_DATATABLE = 'datatable';
   public const VISUALIZATION_PIE     = 'pie';
   public const VISUALIZATION_DONUT   = 'donut';
   public const VISUALIZATION_HALF_PIE = 'half_pie';
   public const VISUALIZATION_HALF_DONUT = 'half_donut';
   public const VISUALIZATION_RATIO   = 'ratio';
   public const VISUALIZATION_GAUGE   = 'gauge';
   public const VISUALIZATION_SPEEDOMETER = 'speedometer';
   public const VISUALIZATION_MULTI_NUMBERS = 'multi_numbers';
   public const VISUALIZATION_SUMMARY_NUMBERS = 'summary_numbers';

   public static function registerWidgetClass(string $class): void
   {
      if (!in_array($class, self::$extra_widget_classes, true)) {
         self::$extra_widget_classes[] = $class;
      }
   }

   public static function getWidgetClasses(): array
   {
      $classes = [
         TicketsOpenWidget::class,
         TicketsNewWidget::class,
         TicketsAssignedWidget::class,
         TicketsPlannedWidget::class,
         TicketsPendingWidget::class,
         TicketsSolvedWidget::class,
         TicketsClosedWidget::class,
         TicketsResolutionRatioWidget::class,
         TicketsLateWidget::class,
         TicketsUnassignedWidget::class,
         TicketsSolvedTodayWidget::class,
         NotificationQueueWidget::class,
         TicketsPriorityMediumWidget::class,
         TicketsPriorityHighWidget::class,
         TicketsPriorityCriticalWidget::class,
         TicketsReceivedByDayWidget::class,
         TicketsSolvedClosedByDayWidget::class,
         TicketsOpenByDayWidget::class,
         TicketsMonthlyEvolutionWidget::class,
         TicketsByEntityWidget::class,
         TicketsByStatusWidget::class,
         TicketsByTypeWidget::class,
         TicketsByRequestTypeWidget::class,
         TicketsByPriorityWidget::class,
         TicketsByLocationWidget::class,
         TicketsByCategoryWidget::class,
         TicketsByGroupWidget::class,
         TicketsByTechnicianWidget::class,
         SlaComplianceWidget::class,
         SlaResponseComplianceWidget::class,
         SlaByTechnicianWidget::class,
         SlaByCategoryWidget::class,
         AverageElapsedSolveTimeClosedWidget::class,
         AverageSolveTimeClosedWidget::class,
         SatisfactionAverageWidget::class,
         SatisfactionAnsweredCountWidget::class,
         SatisfactionResponseRateWidget::class,
         SatisfactionGeneralBreakdownWidget::class,
         SatisfactionBreakdownWidget::class,
         SatisfactionByGroupAverageWidget::class,
         SatisfactionByCategorySummaryWidget::class,
         SatisfactionCommentsWidget::class,
         SatisfactionAnsweredByMonthWidget::class,
         SatisfactionAverageByMonthWidget::class,
         TicketsReopenedWidget::class,
         TicketsPendingReasonsWidget::class,
         TaskEffortByTechnicianWidget::class,
         TotalComputersWidget::class,
         TotalMonitorsWidget::class,
         TotalPrintersWidget::class,
         TotalPhonesWidget::class,
         ComputersByManufacturerWidget::class,
         MonitorsByManufacturerWidget::class,
         ComputersByTypeWidget::class,
         ComputersByLocationWidget::class,
         ComputersByOperatingSystemWidget::class,
         ComputersByProcessorWidget::class,
         ComputersSaoPauloMapWidget::class,
         DistributionDistinctTicketsWidget::class,
         DistributionAutomationRateWidget::class,
         DistributionAutomationIntegralWidget::class,
         DistributionAutomationPartialWidget::class,
         DistributionManualTicketsWidget::class,
         DistributionTransferTicketsWidget::class,
         DistributionSummaryByDistributorWidget::class,
         DistributionByCategoryWidget::class,
         DistributionEvolutionWidget::class,
         DistributionTopDistributorsWidget::class,
         DistributionActuationWidget::class,
         DistributionTopTechniciansWidget::class,
         DistributionTransfersByEntityWidget::class,
         CapacityTeamSummaryWidget::class,
         CapacityTechnicianLoadWidget::class,
         CapacityTechnicianLoadTableWidget::class,
         CapacityAlertsWidget::class,
      ];

      return array_values(array_unique(array_merge($classes, self::$extra_widget_classes)));
   }

   /**
    * @return WidgetInterface[]
    */
   public static function getAll(): array
   {
      $widgets = [];
      foreach (self::getWidgetClasses() as $class) {
         if (!class_exists($class)) {
            Logger::warning('Classe de widget do Dashboard Plus não encontrada: ' . $class);
            continue;
         }

         $widget = new $class();
         if (!$widget instanceof WidgetInterface) {
            Logger::warning('Classe de widget do Dashboard Plus não implementa WidgetInterface: ' . $class);
            continue;
         }

         $widgets[$widget->getKey()] = $widget;
      }

      return $widgets;
   }

   public static function get(string $key): ?WidgetInterface
   {
      $widgets = self::getAll();
      return $widgets[$key] ?? null;
   }

   /**
    * @return WidgetInterface[]
    */
   public static function getEnabledWidgets(): array
   {
      self::ensureDefaultWidgetConfigs();

      $configs = self::getWidgetConfigs();
      $widgets = self::getAll();
      $enabled = [];

      foreach ($configs as $key => $config) {
         if ((int) ($config['is_enabled'] ?? 0) !== 1) {
            continue;
         }
         if (!isset($widgets[$key])) {
            continue;
         }
         if (!$widgets[$key]->canView()) {
            continue;
         }

         $enabled[$key] = $widgets[$key];
      }

      return $enabled;
   }

   public static function getWidgetConfigs(): array
   {
      global $DB;

      $configs = [];
      if (!$DB->tableExists(Config::getWidgetConfigTable())) {
         return $configs;
      }

      $iterator = $DB->request([
         'FROM'  => Config::getWidgetConfigTable(),
         'ORDER' => ['display_order ASC', 'id ASC'],
      ]);

      foreach ($iterator as $row) {
         $configs[(string) $row['widget_key']] = $row;
      }

      return $configs;
   }

   public static function getWidgetConfig(string $key): array
   {
      $widget = self::get($key);
      $defaults = $widget ? $widget->getDefaultSize() : ['width' => 3, 'height' => 2];

      return self::getWidgetConfigs()[$key] ?? [
         'widget_key'    => $key,
         'is_enabled'   => 0,
         'display_order' => 999,
         'width'         => $defaults['width'],
         'height'        => $defaults['height'],
         'config'        => null,
      ];
   }

   public static function getWidgetOptions(array $config): array
   {
      $raw = $config['config'] ?? '';
      if (!is_string($raw) || trim($raw) === '') {
         return [];
      }

      $decoded = json_decode($raw, true);
      return is_array($decoded) ? $decoded : [];
   }

   public static function getAvailableVisualizations(WidgetInterface $widget): array
   {
      switch ($widget->getType()) {
         case 'map_sp':
            return [
               self::VISUALIZATION_DATATABLE => __('Mapa', 'dashboardplus'),
            ];

         case 'metric':
            return [
               self::VISUALIZATION_CARD    => __('Cartão', 'dashboardplus'),
               self::VISUALIZATION_COMPACT => __('Compacto', 'dashboardplus'),
               self::VISUALIZATION_GAUGE   => __('Medidor', 'dashboardplus'),
               self::VISUALIZATION_SPEEDOMETER => __('Velocímetro', 'dashboardplus'),
            ];

         case 'ratio':
            if (self::supportsAdvancedVisualOptions($widget)) {
               return self::getDashboardChartVisualizations();
            }

            return self::getDashboardChartVisualizations() + [
               self::VISUALIZATION_GAUGE => __('Medidor', 'dashboardplus'),
               self::VISUALIZATION_SPEEDOMETER => __('Velocímetro', 'dashboardplus'),
               self::VISUALIZATION_RATIO => __('Faixa proporcional', 'dashboardplus'),
            ];

         case 'breakdown':
            if (self::isTabularWidget($widget)) {
               return self::getDashboardChartVisualizations() + [
                  self::VISUALIZATION_DATATABLE => __('Tabela de dados', 'dashboardplus'),
               ];
            }

            return self::getDashboardChartVisualizations();
      }

      return [
         self::VISUALIZATION_DATATABLE => __('Tabela de dados', 'dashboardplus'),
         self::VISUALIZATION_TABLE => __('Tabela', 'dashboardplus'),
      ];
   }

   public static function getDefaultVisualization(WidgetInterface $widget): string
   {
      switch ($widget->getType()) {
         case 'map_sp':
            return self::VISUALIZATION_DATATABLE;

         case 'metric':
            if (in_array($widget->getKey(), ['satisfaction_average', 'satisfaction_response_rate', 'distribution_automation_rate'], true)) {
               return self::VISUALIZATION_GAUGE;
            }

            return self::VISUALIZATION_CARD;

         case 'ratio':
            if (in_array($widget->getKey(), ['sla_compliance', 'sla_response_compliance'], true)) {
               return self::VISUALIZATION_SPEEDOMETER;
            }

            return self::VISUALIZATION_RATIO;

         case 'breakdown':
            if (self::isTabularWidget($widget)) {
               return self::VISUALIZATION_DATATABLE;
            }

            if (in_array($widget->getKey(), ['tickets_by_status', 'tickets_monthly_evolution', 'tickets_received_by_day', 'tickets_solved_closed_by_day', 'tickets_open_by_day', 'distribution_evolution'], true)) {
               return self::VISUALIZATION_COLUMNS;
            }

            if (in_array($widget->getKey(), ['tickets_by_location', 'tickets_by_category'], true)) {
               return self::VISUALIZATION_DONUT;
            }

            if ($widget->getKey() === 'distribution_actuation') {
               return self::VISUALIZATION_DONUT;
            }

            return self::VISUALIZATION_BARS;
      }

      return self::VISUALIZATION_TABLE;
   }

   public static function normalizeVisualization(WidgetInterface $widget, ?string $visualization): string
   {
      $available = self::getAvailableVisualizations($widget);
      if (is_string($visualization) && array_key_exists($visualization, $available)) {
         return $visualization;
      }

      return self::getDefaultVisualization($widget);
   }

   public static function getWidgetVisualization(WidgetInterface $widget, array $config): string
   {
      $options = self::getWidgetOptions($config);
      return self::normalizeVisualization($widget, $options['visualization'] ?? null);
   }

   public static function supportsAdvancedVisualOptions(WidgetInterface $widget): bool
   {
      return in_array($widget->getType(), ['breakdown', 'ratio'], true)
         && $widget->getType() !== 'map_sp';
   }

   private static function isTabularWidget(WidgetInterface $widget): bool
   {
      return in_array($widget->getKey(), [
         'satisfaction_by_category_summary',
         'satisfaction_comments',
         'distribution_summary_by_distributor',
         'sla_by_technician',
         'sla_by_category',
      ], true);
   }

   private static function getDashboardChartVisualizations(): array
   {
      return [
         self::VISUALIZATION_PIE             => __('Pizza', 'dashboardplus'),
         self::VISUALIZATION_DONUT           => __('Rosca', 'dashboardplus'),
         self::VISUALIZATION_HALF_PIE        => __('Meia torta', 'dashboardplus'),
         self::VISUALIZATION_HALF_DONUT      => __('Meia rosquinha', 'dashboardplus'),
         self::VISUALIZATION_COLUMNS         => __('Barras', 'dashboardplus'),
         self::VISUALIZATION_BARS            => __('Barras horizontais', 'dashboardplus'),
         self::VISUALIZATION_MULTI_NUMBERS   => __('Múltiplos números', 'dashboardplus'),
         self::VISUALIZATION_SUMMARY_NUMBERS => __('Números de resumo', 'dashboardplus'),
         self::VISUALIZATION_TABLE           => __('Tabela', 'dashboardplus'),
      ];
   }

   public static function ensureDefaultWidgetConfigs(): void
   {
      global $DB;

      if (!$DB->tableExists(Config::getWidgetConfigTable())) {
         return;
      }

      $existing = self::getWidgetConfigs();
      $order = count($existing) > 0
         ? max(array_map(static function(array $config): int {
            return (int) ($config['display_order'] ?? 0);
         }, $existing)) + 10
         : 10;

      foreach (self::getAll() as $key => $widget) {
         if (isset($existing[$key])) {
            continue;
         }

         $size = $widget->getDefaultSize();
         try {
            $DB->insert(Config::getWidgetConfigTable(), [
               'widget_key'    => $key,
               'is_enabled'   => 1,
               'display_order' => $order,
               'width'         => (int) ($size['width'] ?? 3),
               'height'        => (int) ($size['height'] ?? 2),
               'config'        => null,
               'date_creation' => date('Y-m-d H:i:s'),
               'date_mod'      => date('Y-m-d H:i:s'),
            ]);
         } catch (Throwable $e) {
            Logger::exception($e, 'Falha ao criar configuração padrão do widget');
         }

         $order += 10;
      }
   }

   public static function applyRecommendedWidgetSizes(): void
   {
      global $DB;

      if (!$DB->tableExists(Config::getWidgetConfigTable())) {
         return;
      }

      foreach (self::getRecommendedWidgetSizes() as $key => $size) {
         $payload = [
            'width'    => max(1, min(12, (int) ($size['width'] ?? 3))),
            'height'   => max(1, min(8, (int) ($size['height'] ?? 2))),
            'date_mod' => date('Y-m-d H:i:s'),
         ];

         if (isset($size['config']) && is_array($size['config'])) {
            $payload['config'] = json_encode($size['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
         }

         $DB->update(Config::getWidgetConfigTable(), $payload, [
            'widget_key' => $key,
         ]);
      }
   }

   private static function getRecommendedWidgetSizes(): array
   {
      return [
         'tickets_new'                 => ['width' => 3, 'height' => 2],
         'tickets_unassigned'          => ['width' => 3, 'height' => 2],
         'tickets_planned'             => ['width' => 3, 'height' => 2],
         'notification_queue'          => ['width' => 6, 'height' => 2],
         'tickets_solved'              => ['width' => 3, 'height' => 2],
         'tickets_pending'             => ['width' => 3, 'height' => 2],
         'tickets_closed'              => ['width' => 3, 'height' => 2],
         'tickets_open'                => ['width' => 3, 'height' => 2],
         'tickets_assigned'            => ['width' => 3, 'height' => 2],
         'tickets_solved_today'        => ['width' => 3, 'height' => 2],
         'tickets_priority_medium'     => ['width' => 3, 'height' => 2],
         'tickets_priority_high'       => ['width' => 3, 'height' => 2],
         'tickets_priority_critical'   => ['width' => 3, 'height' => 2],
         'tickets_late'                => ['width' => 3, 'height' => 2],
         'tickets_received_by_day'     => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_solved_closed_by_day'=> ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_open_by_day'         => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_monthly_evolution'   => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 12, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_resolution_ratio'    => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 6, 'show_labels' => 1, 'gradient' => 0]],
         'tickets_by_entity'           => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_by_status'           => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_by_type'             => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 5, 'show_labels' => 1, 'gradient' => 0]],
         'tickets_by_request_type'     => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 5, 'show_labels' => 1, 'gradient' => 0]],
         'tickets_by_location'         => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 7, 'show_labels' => 1, 'gradient' => 0]],
         'tickets_by_category'         => ['width' => 8, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_by_group'            => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_by_technician'       => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_by_priority'         => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 5, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_pending_reasons'     => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'sla_compliance'              => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_SPEEDOMETER, 'limit' => 6, 'show_labels' => 1, 'gradient' => 0]],
         'sla_response_compliance'     => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_SPEEDOMETER, 'limit' => 6, 'show_labels' => 1, 'gradient' => 0]],
         'sla_by_technician'           => ['width' => 6, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 8, 'show_labels' => 1, 'gradient' => 0]],
         'sla_by_category'             => ['width' => 6, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 8, 'show_labels' => 1, 'gradient' => 0]],
         'average_elapsed_solve_time_closed' => ['width' => 4, 'height' => 2],
         'average_solve_time_closed'   => ['width' => 4, 'height' => 2],
         'satisfaction_average'        => ['width' => 4, 'height' => 2],
         'satisfaction_answered_count' => ['width' => 4, 'height' => 2],
         'satisfaction_response_rate'  => ['width' => 4, 'height' => 2],
         'satisfaction_general_breakdown' => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 6, 'show_labels' => 1, 'gradient' => 0]],
         'satisfaction_breakdown'      => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 6, 'show_labels' => 1, 'gradient' => 0]],
         'satisfaction_by_group_average' => ['width' => 4, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'satisfaction_by_category_summary' => ['width' => 12, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 10, 'show_labels' => 1, 'gradient' => 0]],
         'satisfaction_comments'       => ['width' => 12, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 10, 'show_labels' => 1, 'gradient' => 0]],
         'satisfaction_answered_by_month' => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'satisfaction_average_by_month' => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'tickets_reopened'            => ['width' => 3, 'height' => 2],
         'task_effort_by_technician'   => ['width' => 12, 'height' => 4],
         'asset_total_computers'       => ['width' => 3, 'height' => 2],
         'asset_total_monitors'        => ['width' => 3, 'height' => 2],
         'asset_total_printers'        => ['width' => 3, 'height' => 2],
         'asset_total_phones'          => ['width' => 3, 'height' => 2],
         'asset_computers_sp_map'      => ['width' => 8, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE]],
         'asset_computers_by_location' => ['width' => 4, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 8, 'show_labels' => 1, 'gradient' => 0]],
         'asset_computers_by_manufacturer' => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'asset_monitors_by_manufacturer' => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'asset_computers_by_type'     => ['width' => 4, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 6, 'show_labels' => 1, 'gradient' => 1]],
         'asset_computers_by_os'       => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'asset_computers_by_cpu'      => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 8, 'show_labels' => 1, 'gradient' => 0]],
         'distribution_distinct_tickets' => ['width' => 3, 'height' => 2],
         'distribution_automation_rate'=> ['width' => 3, 'height' => 2],
         'distribution_automation_integral' => ['width' => 3, 'height' => 2],
         'distribution_automation_partial' => ['width' => 3, 'height' => 2],
         'distribution_manual_tickets' => ['width' => 3, 'height' => 2],
         'distribution_transfer_tickets' => ['width' => 3, 'height' => 2],
         'distribution_summary_by_distributor' => ['width' => 12, 'height' => 5, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 10, 'show_labels' => 1, 'gradient' => 0]],
         'distribution_evolution'      => ['width' => 12, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_COLUMNS, 'limit' => 10, 'show_labels' => 1, 'gradient' => 1]],
         'distribution_by_category'    => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'distribution_top_distributors' => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'distribution_actuation'      => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DONUT, 'limit' => 8, 'show_labels' => 1, 'gradient' => 0]],
         'distribution_top_technicians'=> ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'distribution_transfers_by_entity' => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 8, 'show_labels' => 1, 'gradient' => 1]],
         'capacity_team_summary'      => ['width' => 6, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_SUMMARY_NUMBERS, 'limit' => 5, 'show_labels' => 1, 'gradient' => 0]],
         'capacity_technician_load'   => ['width' => 6, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_BARS, 'limit' => 12, 'show_labels' => 1, 'gradient' => 1]],
         'capacity_technician_load_table' => ['width' => 12, 'height' => 4, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 20, 'show_labels' => 1, 'gradient' => 0]],
         'capacity_alerts'            => ['width' => 12, 'height' => 3, 'config' => ['visualization' => self::VISUALIZATION_DATATABLE, 'limit' => 20, 'show_labels' => 1, 'gradient' => 0]],
      ];
   }

   public static function saveWidgetSettings(array $input): void
   {
      global $DB;

      Config::checkConfigureWidgets();
      self::ensureDefaultWidgetConfigs();

      $enabled = $input['enabled_widgets'] ?? [];
      if (!is_array($enabled)) {
         $enabled = [];
      }

      $orders = $input['widget_order'] ?? [];
      if (!is_array($orders)) {
         $orders = [];
      }

      $widths = $input['widget_width'] ?? [];
      if (!is_array($widths)) {
         $widths = [];
      }

      $heights = $input['widget_height'] ?? [];
      if (!is_array($heights)) {
         $heights = [];
      }

      $visualizations = $input['widget_visualization'] ?? [];
      if (!is_array($visualizations)) {
         $visualizations = [];
      }

      $limits = $input['widget_limit'] ?? [];
      if (!is_array($limits)) {
         $limits = [];
      }

      $show_labels = $input['widget_show_labels'] ?? [];
      if (!is_array($show_labels)) {
         $show_labels = [];
      }

      $gradients = $input['widget_gradient'] ?? [];
      if (!is_array($gradients)) {
         $gradients = [];
      }

      $colors = $input['widget_color'] ?? [];
      if (!is_array($colors)) {
         $colors = [];
      }

      $backgrounds = $input['widget_background'] ?? [];
      if (!is_array($backgrounds)) {
         $backgrounds = [];
      }

      $configs = self::getWidgetConfigs();

      foreach (self::getAll() as $key => $widget) {
         $current_config = $configs[$key] ?? self::getWidgetConfig($key);
         $options = self::getWidgetOptions($current_config);
         $default_size = $widget->getDefaultSize();
         $options['visualization'] = self::normalizeVisualization(
            $widget,
            (string) ($visualizations[$key] ?? ($options['visualization'] ?? ''))
         );

         if (self::supportsAdvancedVisualOptions($widget)) {
            $options['limit'] = max(1, min(50, (int) ($limits[$key] ?? ($options['limit'] ?? 10))));
            $options['show_labels'] = array_key_exists($key, $show_labels) ? 1 : 0;
            $options['gradient'] = array_key_exists($key, $gradients) ? 1 : 0;

            $color = self::normalizeColor((string) ($colors[$key] ?? ($options['color'] ?? '')));
            if ($color !== '') {
               $options['color'] = $color;
            } else {
               unset($options['color']);
            }

            $background = self::normalizeColor((string) ($backgrounds[$key] ?? ($options['background'] ?? '')));
            if ($background !== '') {
               $options['background'] = $background;
            } else {
               unset($options['background']);
            }
         }

         $DB->update(Config::getWidgetConfigTable(), [
            'is_enabled'   => array_key_exists($key, $enabled) ? 1 : 0,
            'display_order' => max(0, (int) ($orders[$key] ?? 999)),
            'width'         => max(1, min(12, (int) ($widths[$key] ?? ($current_config['width'] ?? ($default_size['width'] ?? 3))))),
            'height'        => max(1, min(8, (int) ($heights[$key] ?? ($current_config['height'] ?? ($default_size['height'] ?? 2))))),
            'config'        => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'date_mod'      => date('Y-m-d H:i:s'),
         ], [
            'widget_key' => $key,
         ]);
      }

      unset($_SESSION['glpimenu']);
   }

   private static function normalizeColor(string $color): string
   {
      $color = trim($color);
      return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '';
   }
}
