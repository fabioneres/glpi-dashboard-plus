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
         TicketsMonthlyEvolutionWidget::class,
         TicketsByEntityWidget::class,
         TicketsByStatusWidget::class,
         TicketsByTypeWidget::class,
         TicketsByRequestTypeWidget::class,
         TicketsByPriorityWidget::class,
         TicketsByCategoryWidget::class,
         TicketsByGroupWidget::class,
         TicketsByTechnicianWidget::class,
         SlaComplianceWidget::class,
         SlaResponseComplianceWidget::class,
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
         ComputersByTypeWidget::class,
         ComputersByLocationWidget::class,
         ComputersByOperatingSystemWidget::class,
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
            if (!self::supportsAdvancedVisualOptions($widget)
               && in_array($widget->getKey(), ['satisfaction_by_category_summary', 'satisfaction_comments'], true)
            ) {
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
            if (in_array($widget->getKey(), ['satisfaction_by_category_summary', 'satisfaction_comments', 'distribution_summary_by_distributor'], true)) {
               return self::VISUALIZATION_DATATABLE;
            }

            if (in_array($widget->getKey(), ['tickets_by_status', 'tickets_monthly_evolution', 'distribution_evolution'], true)) {
               return self::VISUALIZATION_COLUMNS;
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
      return strpos($widget->getKey(), 'distribution_') === 0;
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
