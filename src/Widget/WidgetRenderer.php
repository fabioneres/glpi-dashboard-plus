<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use Html;

class WidgetRenderer
{
   private const COLORS = [
      '#2563eb',
      '#16a34a',
      '#dc2626',
      '#d97706',
      '#0891b2',
      '#7c3aed',
      '#be123c',
      '#4f46e5',
      '#0f766e',
      '#a16207',
   ];

   public static function render(WidgetInterface $widget, array $data, array $options = []): string
   {
      $visualization = WidgetRegistry::normalizeVisualization($widget, $options['visualization'] ?? null);

      if ($widget->getType() === 'metric') {
         if ($visualization === WidgetRegistry::VISUALIZATION_GAUGE) {
            return self::renderMetricGauge($widget, $data, $options, false);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_SPEEDOMETER) {
            return self::renderMetricGauge($widget, $data, $options, true);
         }

         return self::renderMetric($widget, $data, $options, $visualization);
      }

      if ($widget->getType() === 'map_sp') {
         return self::renderSaoPauloMap($widget, $data);
      }

      if ($widget->getType() === 'ratio') {
         if ($visualization === WidgetRegistry::VISUALIZATION_GAUGE) {
            return self::renderRatioGauge($widget, $data, $options, false);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_SPEEDOMETER) {
            return self::renderRatioGauge($widget, $data, $options, true);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_COLUMNS) {
            return self::renderVerticalBars($widget, $data, $options);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_DONUT) {
            return self::renderPieChart($widget, $data, $options, true);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_HALF_PIE) {
            return self::renderPieChart($widget, $data, $options, false, true);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_HALF_DONUT) {
            return self::renderPieChart($widget, $data, $options, true, true);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_BARS) {
            return self::renderBreakdown($widget, $data, $options);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_MULTI_NUMBERS) {
            return self::renderMultiNumbers($widget, $data, $options);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_SUMMARY_NUMBERS) {
            return self::renderSummaryNumbers($widget, $data, $options);
         }
         if ($visualization === WidgetRegistry::VISUALIZATION_TABLE) {
            return self::renderTable($widget, $data);
         }

         return self::renderRatio($widget, $data);
      }

      if ($visualization === WidgetRegistry::VISUALIZATION_DATATABLE || isset($data['columns'])) {
         return self::renderDataTable($widget, $data);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_TABLE) {
         return self::renderTable($widget, $data);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_COLUMNS) {
         return self::renderVerticalBars($widget, $data, $options);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_DONUT) {
         return self::renderPieChart($widget, $data, $options, true);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_HALF_PIE) {
         return self::renderPieChart($widget, $data, $options, false, true);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_HALF_DONUT) {
         return self::renderPieChart($widget, $data, $options, true, true);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_PIE) {
         return self::renderPieChart($widget, $data, $options, false);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_MULTI_NUMBERS) {
         return self::renderMultiNumbers($widget, $data, $options);
      }
      if ($visualization === WidgetRegistry::VISUALIZATION_SUMMARY_NUMBERS) {
         return self::renderSummaryNumbers($widget, $data, $options);
      }

      return self::renderBreakdown($widget, $data, $options);
   }

   public static function renderError(string $message): string
   {
      return "<div class='dashboardplus-widget-error'>"
         . "<i class='ti ti-alert-triangle'></i>"
         . "<span>" . Html::cleanInputText($message) . "</span>"
         . "</div>";
   }

   private static function renderMetric(WidgetInterface $widget, array $data, array $options, string $visualization): string
   {
      $number = (int) ($data['number'] ?? 0);
      $value = isset($data['value'])
         ? Html::cleanInputText((string) $data['value'])
         : number_format($number, 0, ',', '.');
      $url = (string) ($data['url'] ?? '');
      $label = Html::cleanInputText($widget->getTitle());
      $description = Html::cleanInputText($widget->getDescription());
      $icon = Html::cleanInputText($widget->getIcon());
      $class = $visualization === WidgetRegistry::VISUALIZATION_COMPACT
         ? ' dashboardplus-metric-compact'
         : '';
      $style = self::styleAttribute([
         '--dp-widget-color' => self::getOptionColor($options, self::getRowColor($data, 0)),
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $content = "<div class='dashboardplus-metric{$class}'{$style}>"
         . "<div class='dashboardplus-metric-icon'><i class='{$icon}'></i></div>"
         . "<div class='dashboardplus-metric-body'>"
         . "<div class='dashboardplus-metric-value'>" . $value . "</div>"
         . "<div class='dashboardplus-metric-label'>{$label}</div>"
         . ($description !== '' ? "<div class='dashboardplus-metric-description'>{$description}</div>" : '')
         . "</div>"
         . "</div>";

      if ($url !== '') {
         return "<a class='dashboardplus-widget-link' href='" . self::escapeUrl($url) . "'>{$content}</a>";
      }

      return $content;
   }

   private static function renderMetricGauge(WidgetInterface $widget, array $data, array $options, bool $speedometer): string
   {
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $value = isset($data['value'])
         ? Html::cleanInputText((string) $data['value'])
         : number_format((int) ($data['number'] ?? 0), 0, ',', '.');
      $percent = self::extractMetricPercent($data);
      $color = self::getGaugeColor($percent, self::getOptionColor($options, (string) ($data['color'] ?? '')));
      $needle = -90 + ($percent * 1.8);
      $class = $speedometer ? ' dashboardplus-gauge-speedometer' : '';
      $style = self::styleAttribute([
         '--dp-gauge-value' => (string) $percent,
         '--dp-gauge-color' => $color,
         '--dp-gauge-angle' => $needle . 'deg',
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-gauge-widget{$class}'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";
      $html .= self::renderGaugeSvg($percent, $value, $speedometer);
      $html .= "</div>";

      return $html;
   }

   private static function renderRatioGauge(WidgetInterface $widget, array $data, array $options, bool $speedometer): string
   {
      $rows = self::getRows($data, $options);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());

      if (!count($rows)) {
         return "<div class='dashboardplus-gauge-widget'><div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>"
            . "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
      }

      $total = self::getTotal($data, $rows);
      $main = $rows[0];
      $main_number = (int) ($main['number'] ?? 0);
      $percent = self::getPercent($main_number, $total);
      $display = self::formatPercent($percent);
      $color = self::getGaugeColor($percent, self::getRowColor($main, 0, $options));
      $needle = -90 + ($percent * 1.8);
      $class = $speedometer ? ' dashboardplus-gauge-speedometer' : '';
      $style = self::styleAttribute([
         '--dp-gauge-value' => (string) $percent,
         '--dp-gauge-color' => $color,
         '--dp-gauge-angle' => $needle . 'deg',
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-gauge-widget{$class}'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";
      $html .= self::renderGaugeSvg($percent, $display, $speedometer);
      $html .= "<div class='dashboardplus-gauge-legend'>";
      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $row_percent = self::getPercent($number, $total);
         $row_color = self::getRowColor($row, $index, $options);
         $html .= "<div><span class='dot' style='background: {$row_color}'></span><span>{$row_label}</span><strong>"
            . number_format($number, 0, ',', '.')
            . "</strong><em>" . self::formatPercent($row_percent) . "</em></div>";
      }
      $html .= "</div></div>";

      return $html;
   }

   private static function renderBreakdown(WidgetInterface $widget, array $data, array $options = []): string
   {
      $rows = self::getRows($data, $options);
      $total = max(1, (int) ($data['total'] ?? 0));
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $show_values = self::showLabels($options);
      $class = $show_values ? '' : ' dashboardplus-no-values';
      $style = self::styleAttribute([
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-breakdown{$class}'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-bars'>";
      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $display_value = isset($row['value'])
            ? Html::cleanInputText((string) $row['value'])
            : number_format($number, 0, ',', '.');
         $percent = min(100, max(2, (int) round(($number / $total) * 100)));
         $url = (string) ($row['url'] ?? '');
         $color = self::getRowColor($row, $index, $options);
         $bar_background = self::barBackground($color, $options);

         $line = "<span class='dashboardplus-bar-label'>{$row_label}</span>"
            . "<span class='dashboardplus-bar-track'><span style='width: {$percent}%; background: {$bar_background}'></span></span>"
            . ($show_values ? "<span class='dashboardplus-bar-value'>" . $display_value . "</span>" : '');

         if ($url !== '') {
            $html .= "<a class='dashboardplus-bar-row' href='" . self::escapeUrl($url) . "'>{$line}</a>";
         } else {
            $html .= "<div class='dashboardplus-bar-row'>{$line}</div>";
         }
      }
      $html .= "</div></div>";

      return $html;
   }

   private static function renderVerticalBars(WidgetInterface $widget, array $data, array $options = []): string
   {
      $rows = self::getRows($data, $options);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $show_values = self::showLabels($options);
      $class = $show_values ? '' : ' dashboardplus-no-values';
      $style = self::styleAttribute([
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);
      $max = max(1, max(array_merge([0], array_map(static function(array $row): int {
         return (int) ($row['number'] ?? 0);
      }, $rows))));

      $html = "<div class='dashboardplus-vertical-widget{$class}'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-column-bars'>";
      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $display_value = isset($row['value'])
            ? Html::cleanInputText((string) $row['value'])
            : number_format($number, 0, ',', '.');
         $height = $number > 0 ? max(4, round(($number / $max) * 100, 1)) : 0;
         $url = (string) ($row['url'] ?? '');
         $color = self::getRowColor($row, $index, $options);
         $bar_background = self::barBackground($color, $options);
         $status_class = Html::cleanInputText((string) ($row['status_class'] ?? ''));
         $marker = $status_class !== ''
            ? "<i class='{$status_class}'></i>"
            : "<span class='dashboardplus-column-dot' style='background: {$color}'></span>";

         $line = ($show_values ? "<span class='dashboardplus-column-value'>" . $display_value . "</span>" : '')
            . "<span class='dashboardplus-column-track'><span style='height: {$height}%; background: {$bar_background}'></span></span>"
            . "<span class='dashboardplus-column-label' title='{$row_label}'>{$marker}<span>{$row_label}</span></span>";

         if ($url !== '') {
            $html .= "<a class='dashboardplus-column-item' href='" . self::escapeUrl($url) . "'>{$line}</a>";
         } else {
            $html .= "<div class='dashboardplus-column-item'>{$line}</div>";
         }
      }

      $html .= "</div></div>";

      return $html;
   }

   private static function renderTable(WidgetInterface $widget, array $data): string
   {
      $rows = $data['rows'] ?? [];
      $total = self::getTotal($data, $rows);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());

      $html = "<div class='dashboardplus-table-widget'>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-table-wrap'><table class='dashboardplus-data-table'>";
      $html .= "<thead><tr><th>" . __('Item', 'dashboardplus') . "</th><th>" . __('Qtd.', 'dashboardplus') . "</th><th>%</th></tr></thead><tbody>";

      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $percent = self::getPercent($number, $total);
         $url = (string) ($row['url'] ?? '');
         $color = self::getRowColor($row, $index);
         $status_class = Html::cleanInputText((string) ($row['status_class'] ?? ''));
         $marker = $status_class !== ''
            ? "<i class='{$status_class}'></i>"
            : "<span class='dashboardplus-table-dot' style='background: {$color}'></span>";
         $name = "<span class='dashboardplus-table-label'>{$marker}<span>{$row_label}</span></span>";
         if ($url !== '') {
            $name = "<a href='" . self::escapeUrl($url) . "'>{$name}</a>";
         }

         $html .= "<tr><td>{$name}</td><td>" . number_format($number, 0, ',', '.') . "</td><td>" . self::formatPercent($percent) . "</td></tr>";
      }

      $total_percent = $total > 0 ? '100%' : '0%';
      $html .= "</tbody><tfoot><tr><th>" . __('Total', 'dashboardplus') . "</th><th>" . number_format($total, 0, ',', '.') . "</th><th>{$total_percent}</th></tr></tfoot>";
      $html .= "</table></div></div>";

      return $html;
   }

   private static function renderDataTable(WidgetInterface $widget, array $data): string
   {
      $rows = $data['rows'] ?? [];
      $columns = $data['columns'] ?? [];
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());

      if ($columns === [] && count($rows)) {
         $columns = array_keys(reset($rows));
      }

      $html = "<div class='dashboardplus-table-widget'>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows) || !count($columns)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-table-wrap'><table class='dashboardplus-data-table dashboardplus-wide-table'>";
      $html .= "<thead><tr>";
      foreach ($columns as $column) {
         $html .= "<th>" . Html::cleanInputText((string) $column) . "</th>";
      }
      $html .= "</tr></thead><tbody>";

      foreach ($rows as $row) {
         $html .= "<tr>";
         foreach ($columns as $column) {
            $value = Html::cleanInputText((string) ($row[$column] ?? '-'));
            $html .= "<td>{$value}</td>";
         }
         $html .= "</tr>";
      }

      $html .= "</tbody></table></div></div>";

      return $html;
   }

   private static function renderSaoPauloMap(WidgetInterface $widget, array $data): string
   {
      $markers = $data['markers'] ?? [];
      $unmapped = $data['unmapped'] ?? [];
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $total = (int) ($data['total'] ?? 0);
      $max = max(1, max(array_merge([0], array_map(static function(array $marker): int {
         return (int) ($marker['number'] ?? 0);
      }, $markers))));

      $html = "<div class='dashboardplus-map-widget'>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";
      $html .= "<div class='dashboardplus-map-layout'>";
      $html .= "<div class='dashboardplus-sp-map'>";
      $html .= "<svg viewBox='0 0 720 420' role='img' aria-label='{$label}'>";
      $html .= "<path class='dashboardplus-sp-shape' d='M63.9 208.8 L100.6 188.1 L108.4 180.5 L113.0 170.1 L130.4 150.6 L127.3 142.5 L135.9 139.8 L142.6 129.8 L142.1 115.2 L159.4 101.5 L158.3 89.6 L161.1 82.1 L177.4 63.2 L193.4 57.9 L203.3 41.1 L236.5 24.0 L244.5 29.7 L261.2 30.1 L267.4 33.9 L298.4 32.5 L306.3 37.6 L317.7 36.3 L314.7 45.8 L320.1 59.2 L323.9 59.8 L331.0 49.2 L334.2 49.1 L337.4 52.8 L337.6 65.3 L342.3 68.5 L346.8 49.9 L375.4 46.4 L386.2 48.3 L386.5 40.8 L395.2 48.5 L401.4 46.9 L404.3 41.2 L409.5 47.3 L412.6 38.1 L422.6 37.5 L427.2 42.1 L438.6 36.4 L440.4 42.2 L452.7 50.0 L454.4 53.5 L449.9 62.2 L450.3 69.0 L459.6 73.7 L463.4 82.1 L462.2 86.4 L457.4 88.0 L453.8 98.3 L460.3 104.9 L462.0 118.5 L465.7 120.2 L470.1 129.6 L469.2 134.4 L485.8 130.3 L489.7 133.3 L492.5 130.3 L495.8 135.7 L503.0 137.6 L502.4 147.3 L499.2 151.7 L495.4 151.5 L495.1 157.6 L490.7 162.3 L496.0 174.3 L488.9 179.3 L497.0 182.5 L491.9 185.1 L488.6 193.9 L492.4 201.2 L509.8 209.6 L508.0 211.9 L510.8 217.9 L505.1 220.3 L514.7 224.4 L511.4 231.8 L514.0 234.1 L527.5 231.0 L527.9 235.3 L543.3 228.2 L544.7 232.2 L548.1 229.2 L551.3 231.0 L553.7 226.6 L556.5 228.1 L555.5 221.9 L549.3 221.9 L557.8 217.1 L556.2 212.2 L560.0 212.1 L559.6 217.1 L565.7 213.7 L565.2 217.1 L568.1 217.2 L572.3 212.9 L577.5 217.2 L585.9 214.8 L595.8 206.7 L617.3 200.5 L621.9 201.9 L629.0 214.3 L637.5 216.4 L645.8 211.9 L647.7 214.6 L656.4 213.9 L660.9 218.9 L653.4 229.2 L640.3 232.8 L638.4 230.2 L618.4 239.3 L616.2 251.4 L611.9 255.6 L623.0 265.3 L615.0 266.7 L610.6 263.0 L600.3 268.8 L603.2 271.6 L598.9 275.6 L593.3 273.6 L593.5 277.0 L589.1 276.7 L590.2 279.7 L577.1 282.4 L575.6 296.4 L543.7 292.0 L529.2 296.7 L525.0 307.1 L517.9 310.8 L516.2 306.5 L511.3 305.8 L510.3 310.0 L503.7 310.5 L481.8 321.6 L469.9 329.9 L469.3 335.6 L418.0 369.2 L408.8 378.6 L408.9 386.3 L396.0 396.0 L401.1 390.5 L395.0 391.9 L389.8 388.0 L392.2 384.7 L385.9 373.5 L380.3 379.7 L375.1 373.7 L367.2 381.8 L363.6 378.5 L364.6 364.7 L369.2 357.4 L363.6 352.9 L358.3 355.3 L347.0 351.8 L338.3 353.8 L333.2 350.4 L330.9 353.8 L315.0 353.1 L314.2 345.1 L322.0 330.9 L316.4 328.5 L311.6 322.2 L312.8 317.0 L306.6 313.7 L301.1 302.8 L294.4 297.8 L297.7 295.6 L298.5 287.8 L294.0 283.5 L293.2 274.9 L297.4 269.3 L293.9 267.3 L295.8 266.3 L292.6 264.0 L289.9 251.6 L285.7 247.2 L274.2 244.0 L275.0 240.6 L269.2 233.6 L255.9 237.4 L245.4 237.0 L242.8 234.2 L234.2 236.9 L223.7 233.5 L218.5 238.0 L214.1 236.5 L214.9 233.4 L208.5 226.8 L190.5 223.9 L183.2 218.2 L152.7 218.3 L150.4 214.8 L142.3 215.5 L126.7 208.0 L123.2 216.6 L118.7 218.6 L116.9 214.7 L100.0 216.0 L94.2 211.4 L86.9 215.4 L68.4 211.6 L60.6 217.6 L59.2 213.5 L63.9 208.8 Z'/>";

      foreach ($markers as $marker) {
         $point = self::projectSaoPauloPoint((float) $marker['longitude'], (float) $marker['latitude']);
         if ($point === null) {
            continue;
         }
         $city = Html::cleanInputText((string) ($marker['label'] ?? '-'));
         $number = (int) ($marker['number'] ?? 0);
         $radius = 7 + (18 * ($number / $max));
         $x = round($point[0], 1);
         $y = round($point[1], 1);
         $formatted = number_format($number, 0, ',', '.');
         $html .= "<g class='dashboardplus-map-marker'>";
         $html .= "<circle cx='{$x}' cy='{$y}' r='" . round($radius, 1) . "'></circle>";
         $html .= "<text x='{$x}' y='" . ($y - $radius - 6) . "'>{$city}: {$formatted}</text>";
         $html .= "</g>";
      }

      $html .= "</svg></div>";
      $html .= "<div class='dashboardplus-map-summary'>";
      $html .= "<strong>" . number_format($total, 0, ',', '.') . "</strong>";
      $html .= "<span>" . __('computadores localizados', 'dashboardplus') . "</span>";

      if (!count($markers) && !count($unmapped)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum ativo com cidade em São Paulo encontrado.', 'dashboardplus') . "</div>";
      } else {
         $html .= "<div class='dashboardplus-map-list'>";
         foreach (array_slice($markers, 0, 8) as $marker) {
            $html .= "<div><span>" . Html::cleanInputText((string) ($marker['label'] ?? '-')) . "</span><b>"
               . number_format((int) ($marker['number'] ?? 0), 0, ',', '.')
               . "</b></div>";
         }
         foreach (array_slice($unmapped, 0, 5) as $row) {
            $html .= "<div class='is-muted'><span>" . Html::cleanInputText((string) ($row['label'] ?? '-')) . "</span><b>"
               . number_format((int) ($row['number'] ?? 0), 0, ',', '.')
               . "</b></div>";
         }
         $html .= "</div>";
      }

      if (count($unmapped)) {
         $html .= "<small>" . __('Algumas cidades não possuem coordenada cadastrada ou reconhecida.', 'dashboardplus') . "</small>";
      }
      $html .= "</div></div></div>";

      return $html;
   }

   private static function projectSaoPauloPoint(float $longitude, float $latitude): ?array
   {
      $min_lon = -53.10960963158027;
      $max_lon = -44.160518123245765;
      $min_lat = -25.311828770311763;
      $max_lat = -19.779193765159107;
      $scale = 67.23740128411666;
      $offset_x = 59.14317156291605;
      $offset_y = 24.0;

      if ($longitude < $min_lon || $longitude > $max_lon || $latitude < $min_lat || $latitude > $max_lat) {
         return null;
      }

      $x = $offset_x + (($longitude - $min_lon) * $scale);
      $y = $offset_y + (($max_lat - $latitude) * $scale);

      return [$x, $y];
   }

   private static function renderPieChart(WidgetInterface $widget, array $data, array $options, bool $donut, bool $half = false): string
   {
      $rows = self::getRows($data, $options);
      $total = self::getTotal($data, $rows);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $mode_class = $donut ? ' dashboardplus-chart-donut' : ' dashboardplus-chart-pie';
      $mode_class .= $half ? ' dashboardplus-chart-half' : '';
      $show_values = self::showLabels($options);
      $class = $show_values ? '' : ' dashboardplus-no-values';
      $style = self::styleAttribute([
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-chart-widget{$mode_class}{$class}'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows) || $total <= 0) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $gradient = self::buildConicGradient($rows, $total, $options, $half);
      $html .= "<div class='dashboardplus-chart-layout'>";
      $html .= "<div class='dashboardplus-chart-visual' style='background: {$gradient};'><span><strong>" . number_format($total, 0, ',', '.') . "</strong><small>" . __('Total', 'dashboardplus') . "</small></span></div>";
      $html .= "<div class='dashboardplus-chart-legend'>";

      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $percent = self::getPercent($number, $total);
         $url = (string) ($row['url'] ?? '');
         $color = self::getRowColor($row, $index, $options);
         $line = "<span class='dot' style='background: {$color}'></span>"
            . "<span>{$row_label}</span>"
            . ($show_values ? "<strong>" . number_format($number, 0, ',', '.') . "</strong>" : '')
            . ($show_values ? "<em>" . self::formatPercent($percent) . "</em>" : '');

         if ($url !== '') {
            $html .= "<a href='" . self::escapeUrl($url) . "'>{$line}</a>";
         } else {
            $html .= "<div>{$line}</div>";
         }
      }

      $html .= "</div></div></div>";

      return $html;
   }

   private static function renderMultiNumbers(WidgetInterface $widget, array $data, array $options): string
   {
      $rows = self::getRows($data, $options);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $show_values = self::showLabels($options);
      $style = self::styleAttribute([
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-numbers-widget'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-number-grid'>";
      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $display_value = isset($row['value'])
            ? Html::cleanInputText((string) $row['value'])
            : number_format($number, 0, ',', '.');
         $color = self::getRowColor($row, $index, $options);
         $html .= "<div class='dashboardplus-number-item' style='--dp-widget-color: {$color}'>";
         $html .= "<strong>" . ($show_values ? $display_value : number_format($number, 0, ',', '.')) . "</strong>";
         $html .= "<span>{$row_label}</span>";
         $html .= "</div>";
      }
      $html .= "</div></div>";

      return $html;
   }

   private static function renderSummaryNumbers(WidgetInterface $widget, array $data, array $options): string
   {
      $rows = self::getRows($data, $options);
      $total = self::getTotal($data, $rows);
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());
      $style = self::styleAttribute([
         '--dp-widget-color' => self::getOptionColor($options, '#2563eb'),
         '--dp-widget-background' => self::getOptionBackground($options),
      ]);

      $html = "<div class='dashboardplus-summary-widget'{$style}>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";
      $html .= "<div class='dashboardplus-summary-total'><strong>" . number_format($total, 0, ',', '.') . "</strong><span>" . __('Total', 'dashboardplus') . "</span></div>";

      if (count($rows)) {
         $html .= "<div class='dashboardplus-summary-list'>";
         foreach (array_slice($rows, 0, 4) as $index => $row) {
            $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
            $number = (int) ($row['number'] ?? 0);
            $percent = self::getPercent($number, $total);
            $color = self::getRowColor($row, $index, $options);
            $html .= "<div><span class='dot' style='background: {$color}'></span><span>{$row_label}</span><strong>"
               . number_format($number, 0, ',', '.')
               . "</strong><em>" . self::formatPercent($percent) . "</em></div>";
         }
         $html .= "</div>";
      }

      $html .= "</div>";
      return $html;
   }

   private static function renderRatio(WidgetInterface $widget, array $data): string
   {
      $rows = $data['rows'] ?? [];
      $total = max(1, array_sum(array_map(static function(array $row): int {
         return (int) ($row['number'] ?? 0);
      }, $rows)));
      $label = Html::cleanInputText($widget->getTitle());
      $icon = Html::cleanInputText($widget->getIcon());

      $html = "<div class='dashboardplus-ratio'>";
      $html .= "<div class='dashboardplus-widget-title'><i class='{$icon}'></i><span>{$label}</span></div>";

      if (!count($rows)) {
         $html .= "<div class='dashboardplus-empty'>" . __('Nenhum dado encontrado', 'dashboardplus') . "</div></div>";
         return $html;
      }

      $html .= "<div class='dashboardplus-ratio-strip'>";
      foreach ($rows as $index => $row) {
         $number = (int) ($row['number'] ?? 0);
         $percent = max(0, round(($number / $total) * 100, 1));
         $html .= "<span class='dashboardplus-ratio-part part-{$index}' style='width: {$percent}%; background: " . self::getRowColor($row, $index) . "'></span>";
      }
      $html .= "</div>";

      $html .= "<div class='dashboardplus-ratio-legend'>";
      foreach ($rows as $index => $row) {
         $row_label = Html::cleanInputText((string) ($row['label'] ?? '-'));
         $number = (int) ($row['number'] ?? 0);
         $percent = round(($number / $total) * 100, 1);
         $color = self::getRowColor($row, $index);
         $html .= "<div><span class='dot part-{$index}' style='background: {$color}'></span><strong>"
            . number_format($number, 0, ',', '.')
            . "</strong><span>{$row_label}</span><em>{$percent}%</em></div>";
      }
      $html .= "</div></div>";

      return $html;
   }

   private static function renderGaugeSvg(float $percent, string $value, bool $speedometer): string
   {
      $safe_value = Html::cleanInputText($value);
      $percent_label = self::formatPercent($percent);
      $dash = round(max(0, min(100, $percent)) * 2.2, 1);
      $needle = -90 + ($percent * 1.8);
      $ticks = $speedometer
         ? "<path class='dashboardplus-gauge-zone zone-low' d='M32 122 A78 78 0 0 1 73 53'/>"
            . "<path class='dashboardplus-gauge-zone zone-mid' d='M73 53 A78 78 0 0 1 147 53'/>"
            . "<path class='dashboardplus-gauge-zone zone-high' d='M147 53 A78 78 0 0 1 188 122'/>"
         : '';

      return "<div class='dashboardplus-gauge-visual'>"
         . "<svg viewBox='0 0 220 140' role='img' aria-label='{$safe_value}'>"
         . "<path class='dashboardplus-gauge-bg' d='M32 122 A78 78 0 0 1 188 122'/>"
         . $ticks
         . "<path class='dashboardplus-gauge-fill' pathLength='220' stroke-dasharray='{$dash} 220' d='M32 122 A78 78 0 0 1 188 122'/>"
         . ($speedometer ? "<g class='dashboardplus-gauge-needle' style='transform: rotate({$needle}deg)'><line x1='110' y1='122' x2='110' y2='52'/><circle cx='110' cy='122' r='6'/></g>" : '')
         . "<text class='dashboardplus-gauge-min' x='30' y='136'>0</text>"
         . "<text class='dashboardplus-gauge-max' x='184' y='136'>100</text>"
         . "</svg>"
         . "<div class='dashboardplus-gauge-value'><strong>{$safe_value}</strong><span>{$percent_label}</span></div>"
         . "</div>";
   }

   private static function escapeUrl(string $url): string
   {
      return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
   }

   private static function getTotal(array $data, array $rows): int
   {
      $total = (int) ($data['total'] ?? 0);
      if ($total > 0) {
         return $total;
      }

      return array_sum(array_map(static function(array $row): int {
         return (int) ($row['number'] ?? 0);
      }, $rows));
   }

   private static function getPercent(int $number, int $total): float
   {
      if ($total <= 0) {
         return 0.0;
      }

      return round(($number / $total) * 100, 1);
   }

   private static function formatPercent(float $percent): string
   {
      $decimals = abs($percent - round($percent)) < 0.01 ? 0 : 1;
      return number_format($percent, $decimals, ',', '.') . '%';
   }

   private static function extractMetricPercent(array $data): float
   {
      $value = (string) ($data['value'] ?? '');
      if (preg_match('/([0-9]+(?:[\\.,][0-9]+)?)\\s*%/', $value, $matches)) {
         return max(0, min(100, (float) str_replace(',', '.', $matches[1])));
      }

      if (preg_match('/([0-9]+(?:[\\.,][0-9]+)?)\\s*\\/\\s*5/', $value, $matches)) {
         return max(0, min(100, ((float) str_replace(',', '.', $matches[1]) / 5) * 100));
      }

      $number = (float) ($data['number'] ?? 0);
      if ($number > 100 && $number <= 500) {
         return max(0, min(100, $number / 5));
      }

      return max(0, min(100, $number));
   }

   private static function getGaugeColor(float $percent, string $fallback = ''): string
   {
      if (preg_match('/^#[0-9a-fA-F]{6}$/', $fallback)) {
         return $fallback;
      }

      if ($percent >= 80) {
         return '#16a34a';
      }
      if ($percent >= 55) {
         return '#d97706';
      }

      return '#dc2626';
   }

   private static function buildConicGradient(array $rows, int $total, array $options = [], bool $half = false): string
   {
      $segments = [];
      $start = 0.0;
      $range = $half ? 180.0 : 100.0;

      foreach ($rows as $index => $row) {
         $number = (int) ($row['number'] ?? 0);
         if ($number <= 0) {
            continue;
         }

         $end = min($range, $start + (($number / $total) * $range));
         $color = self::getRowColor($row, $index, $options);
         $unit = $half ? 'deg' : '%';
         $segments[] = "{$color} " . round($start, 2) . $unit . ' ' . round($end, 2) . $unit;
         $start = $end;
      }

      if ($segments === []) {
         return 'conic-gradient(#e7edf5 0% 100%)';
      }

      if ($half) {
         if ($start < 180) {
            $segments[] = '#e7edf5 ' . round($start, 2) . 'deg 180deg';
         }
         $segments[] = 'transparent 180deg 360deg';
         return 'conic-gradient(from 270deg, ' . implode(', ', $segments) . ')';
      } elseif ($start < 100) {
         $segments[] = self::getColor(count($segments)) . ' ' . round($start, 2) . '% 100%';
      }

      return 'conic-gradient(' . implode(', ', $segments) . ')';
   }

   private static function getColor(int $index): string
   {
      return self::COLORS[$index % count(self::COLORS)];
   }

   private static function getRowColor(array $row, int $index, array $options = []): string
   {
      if ($index === 0) {
         $option_color = self::getOptionColor($options);
         if ($option_color !== '') {
            return $option_color;
         }
      }

      $color = (string) ($row['color'] ?? '');
      if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
         return $color;
      }

      return self::getColor($index);
   }

   private static function getRows(array $data, array $options): array
   {
      $rows = $data['rows'] ?? [];
      $limit = (int) ($options['limit'] ?? 0);
      if ($limit > 0) {
         return array_slice($rows, 0, max(1, min(50, $limit)));
      }

      return $rows;
   }

   private static function showLabels(array $options): bool
   {
      return (int) ($options['show_labels'] ?? 1) === 1;
   }

   private static function getOptionColor(array $options, string $fallback = ''): string
   {
      $color = (string) ($options['color'] ?? '');
      if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
         return $color;
      }

      return preg_match('/^#[0-9a-fA-F]{6}$/', $fallback) ? $fallback : '';
   }

   private static function getOptionBackground(array $options): string
   {
      $background = (string) ($options['background'] ?? '');
      return preg_match('/^#[0-9a-fA-F]{6}$/', $background) ? $background : '';
   }

   private static function barBackground(string $color, array $options): string
   {
      if ((int) ($options['gradient'] ?? 0) !== 1) {
         return $color;
      }

      return 'linear-gradient(90deg, ' . $color . ', #facc15)';
   }

   private static function styleAttribute(array $variables): string
   {
      $parts = [];
      foreach ($variables as $name => $value) {
         if (!preg_match('/^--[a-z0-9-]+$/', $name)) {
            continue;
         }
         if (!preg_match('/^(#[0-9a-fA-F]{6}|-?[0-9]+(?:\\.[0-9]+)?(?:deg|%)?)$/', (string) $value)) {
            continue;
         }
         $parts[] = $name . ': ' . $value;
      }

      if ($parts === []) {
         return '';
      }

      return " style='" . Html::cleanInputText(implode('; ', $parts)) . "'";
   }
}
