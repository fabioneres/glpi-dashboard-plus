<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use Dropdown;
use Entity;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;
use Html;
use Session;

class ConfigPage
{
   public static function handleSubmit(array $post): void
   {
      if (!isset($post['save_dashboardplus_config'])) {
         return;
      }

      Config::checkAdmin();
      Config::saveSettings($post);
      Config::saveEntityScope($post);
      Config::saveEntityAvailability($post);

      if (Config::canConfigureWidgets()) {
         WidgetRegistry::saveWidgetSettings($post);
      }

      Session::addMessageAfterRedirect(__('Configurações do Dashboard Plus salvas.', 'dashboardplus'), true, INFO);
      Html::redirect(Config::pluginUrl('/front/config.form.php'));
   }

   public static function show(): void
   {
      Config::checkAdmin();

      $settings = Config::getSettings();
      $entity_ids = Config::getConfiguredEntityIds();
      $entity_rows = Config::getConfiguredEntityRows();
      $entities_recursive = !count($entity_rows) || (int) ($entity_rows[0]['is_recursive'] ?? 1) === 1;
      $widgets = WidgetRegistry::getAll();
      $widget_configs = WidgetRegistry::getWidgetConfigs();
      $entity_configs = Config::getEntityConfigRows();
      $available_entities = self::getAvailableEntities();
      $capacity_config = $settings['capacity_config'] ?? Config::getDefaultCapacityConfig();

      $theme_class = Html::cleanInputText(Config::getThemeClass($settings));
      $theme_style = Config::getThemeStyleAttribute($settings);

      echo "<div class='dashboardplus-config {$theme_class}'{$theme_style}>";
      echo "<form method='post' action='" . Config::pluginUrl('/front/config.form.php') . "'>";

      echo "<div class='dashboardplus-config-header'>";
      echo "<h1><i class='" . Config::getIcon() . "'></i> " . __('Configurações do Dashboard Plus', 'dashboardplus') . "</h1>";
      echo "<div class='dashboardplus-header-actions'>";
      echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/dashboard.php') . "'>";
      echo "<i class='ti ti-arrow-left'></i> " . __('Voltar ao painel', 'dashboardplus');
      echo "</a>";
      echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/about.php') . "'>";
      echo "<i class='ti ti-info-circle'></i> " . __('Sobre', 'dashboardplus');
      echo "</a>";
      echo "</div>";
      echo "</div>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Comportamento geral', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-settings-grid'>";
      self::numberField('default_period_days', __('Período padrão em dias', 'dashboardplus'), (int) $settings['default_period_days'], 1, 366);
      self::numberField('refresh_interval', __('Intervalo de atualização automática em segundos', 'dashboardplus'), (int) $settings['refresh_interval'], 30, 3600);
      self::numberField('cache_ttl', __('Tempo de cache dos widgets em segundos', 'dashboardplus'), (int) $settings['cache_ttl'], 30, 3600);
      self::selectField('dashboard_theme', __('Tema visual', 'dashboardplus'), (string) $settings['dashboard_theme'], Config::getThemeOptions());
      self::selectField('chart_palette', __('Paleta dos gráficos', 'dashboardplus'), (string) $settings['chart_palette'], Config::getPaletteOptions());
      self::colorField('accent_color', __('Cor de destaque', 'dashboardplus'), (string) $settings['accent_color']);
      self::switchField('auto_refresh', __('Habilitar atualização automática', 'dashboardplus'), (int) $settings['auto_refresh']);
      self::switchField('use_cache', __('Habilitar cache dos widgets', 'dashboardplus'), (int) $settings['use_cache']);
      echo "</div>";
      echo "</section>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Escopo de entidades', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-entity-scope'>";
      echo "<label>";
      echo "<span>" . __('Entidades consideradas', 'dashboardplus') . "</span>";
      if ($available_entities !== []) {
         Dropdown::showFromArray('dashboardplus_entities_id', $available_entities, [
            'values'               => $entity_ids,
            'multiple'             => true,
            'width'                => '100%',
            'display_emptychoice'  => false,
         ]);
      } else {
         echo "<span class='text-muted'>" . __('Nenhuma entidade acessível encontrada.', 'dashboardplus') . "</span>";
      }
      echo "</label>";
      echo "<label class='dashboardplus-inline-switch dashboardplus-entity-recursive'>";
      echo "<span>" . __('Incluir entidades filhas', 'dashboardplus') . "</span>";
      echo "<input type='hidden' name='dashboardplus_entities_recursive' value='0'>";
      echo "<input type='checkbox' name='dashboardplus_entities_recursive' value='1'" . ($entities_recursive ? ' checked' : '') . ">";
      echo "</label>";
      echo "<small>" . __('Deixe em branco para usar as entidades visíveis para cada usuário.', 'dashboardplus') . "</small>";
      echo "</div>";
      echo "</section>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Disponibilidade por entidade', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-settings-grid'>";
      self::switchField('entity_default_enabled', __('Habilitar por padrão quando não houver regra específica', 'dashboardplus'), (int) $settings['entity_default_enabled']);
      echo "<label class='dashboardplus-full-field'>";
      echo "<span>" . __('Abas habilitadas por padrão', 'dashboardplus') . "</span>";
      self::tabsCheckboxes('default_enabled_tabs', $settings['default_enabled_tabs'] ?? Config::getDashboardKeys());
      echo "</label>";
      echo "</div>";

      echo "<div class='dashboardplus-widget-config-list'>";
      foreach ($entity_configs as $row) {
         $entities_id = (int) ($row['entities_id'] ?? 0);
         $tabs = $row['enabled_tabs'] ?? [];
         echo "<div class='dashboardplus-widget-config-row dashboardplus-entity-config-row'>";
         echo "<div class='dashboardplus-widget-config-title'>";
         echo "<strong>" . Html::cleanInputText(Dropdown::getDropdownName('glpi_entities', $entities_id)) . "</strong>";
         echo "<small>" . sprintf(__('Regra explícita da entidade ID %s', 'dashboardplus'), $entities_id) . "</small>";
         echo "</div>";
         echo "<label class='dashboardplus-inline-option'>";
         echo "<input type='hidden' name='entity_config[{$entities_id}][is_enabled]' value='0'>";
         echo "<input type='checkbox' name='entity_config[{$entities_id}][is_enabled]' value='1'" . ((int) ($row['is_enabled'] ?? 0) === 1 ? ' checked' : '') . ">";
         echo "<span>" . __('Ativo', 'dashboardplus') . "</span>";
         echo "</label>";
         echo "<label class='dashboardplus-inline-option'>";
         echo "<input type='hidden' name='entity_config[{$entities_id}][is_recursive]' value='0'>";
         echo "<input type='checkbox' name='entity_config[{$entities_id}][is_recursive]' value='1'" . ((int) ($row['is_recursive'] ?? 0) === 1 ? ' checked' : '') . ">";
         echo "<span>" . __('Recursivo', 'dashboardplus') . "</span>";
         echo "</label>";
         echo "<div class='dashboardplus-widget-advanced dashboardplus-tabs-config'>";
         self::tabsCheckboxes("entity_config[{$entities_id}][tabs]", $tabs);
         echo "</div>";
         echo "</div>";
      }
      echo "</div>";

      echo "<div class='dashboardplus-entity-scope mt-3'>";
      echo "<h3>" . __('Adicionar ou atualizar entidade', 'dashboardplus') . "</h3>";
      echo "<label>";
      echo "<span>" . __('Entidade', 'dashboardplus') . "</span>";
      Dropdown::showFromArray('new_entity_config[entities_id]', $available_entities, [
         'value'               => 0,
         'width'               => '100%',
         'display_emptychoice' => true,
      ]);
      echo "</label>";
      echo "<label class='dashboardplus-inline-switch'>";
      echo "<span>" . __('Ativo', 'dashboardplus') . "</span>";
      echo "<input type='hidden' name='new_entity_config[is_enabled]' value='0'>";
      echo "<input type='checkbox' name='new_entity_config[is_enabled]' value='1' checked>";
      echo "</label>";
      echo "<label class='dashboardplus-inline-switch'>";
      echo "<span>" . __('Recursivo', 'dashboardplus') . "</span>";
      echo "<input type='hidden' name='new_entity_config[is_recursive]' value='0'>";
      echo "<input type='checkbox' name='new_entity_config[is_recursive]' value='1'>";
      echo "</label>";
      echo "<label class='dashboardplus-full-field'>";
      echo "<span>" . __('Abas', 'dashboardplus') . "</span>";
      self::tabsCheckboxes('new_entity_config[tabs]', Config::getDashboardKeys());
      echo "</label>";
      echo "</div>";
      echo "</section>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Capacidade operacional', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-settings-grid'>";
      self::switchField('capacity_enabled', __('Habilitar gestão de capacidade', 'dashboardplus'), (int) $settings['capacity_enabled']);
      self::numberField('capacity_cache_ttl', __('Cache da capacidade em segundos', 'dashboardplus'), (int) $settings['capacity_cache_ttl'], 30, 3600);
      self::numberField('capacity_standard_weekly_hours', __('Jornada semanal de referência', 'dashboardplus'), (int) ($capacity_config['standard_weekly_hours'] ?? 40), 1, 80);
      self::numberField('capacity_sla_attention_percent', __('SLA em atenção a partir de % consumido', 'dashboardplus'), (int) ($capacity_config['sla_attention_percent'] ?? 70), 1, 99);
      self::numberField('capacity_sla_critical_percent', __('SLA crítico a partir de % consumido', 'dashboardplus'), (int) ($capacity_config['sla_critical_percent'] ?? 90), 1, 100);
      echo "</div>";
      echo "<div class='dashboardplus-settings-grid'>";
      foreach (range(1, 6) as $priority) {
         self::numberField("capacity_priority_weights[{$priority}]", sprintf(__('Peso prioridade %s', 'dashboardplus'), $priority), (int) ($capacity_config['priority_weights'][$priority] ?? $priority), 0, 20);
      }
      foreach (['low' => __('Baixa até', 'dashboardplus'), 'moderate' => __('Moderada até', 'dashboardplus'), 'high' => __('Alta até', 'dashboardplus')] as $key => $label) {
         self::numberField("capacity_load_thresholds[{$key}]", $label, (int) ($capacity_config['load_thresholds'][$key] ?? 0), 0, 999);
      }
      foreach (['comfortable' => __('Peso SLA confortável', 'dashboardplus'), 'attention' => __('Peso SLA atenção', 'dashboardplus'), 'critical' => __('Peso SLA crítico', 'dashboardplus'), 'violated' => __('Peso SLA violado', 'dashboardplus')] as $key => $label) {
         self::numberField("capacity_sla_weights[{$key}]", $label, (int) ($capacity_config['sla_weights'][$key] ?? 0), 0, 20);
      }
      echo "</div>";
      echo "<small>" . __('Sem tarefas planejadas, o painel exibe índice de carga operacional, não percentual de ocupação em horas.', 'dashboardplus') . "</small>";
      echo "</section>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Widgets', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-widget-config-list'>";
      foreach ($widgets as $key => $widget) {
         $config = $widget_configs[$key] ?? WidgetRegistry::getWidgetConfig($key);
         $enabled = (int) ($config['is_enabled'] ?? 0) === 1;
         $order = (int) ($config['display_order'] ?? 999);
         $default_size = $widget->getDefaultSize();
         $width = max(1, min(12, (int) ($config['width'] ?? ($default_size['width'] ?? 3))));
         $height = max(1, min(8, (int) ($config['height'] ?? ($default_size['height'] ?? 2))));
         $visualization = WidgetRegistry::getWidgetVisualization($widget, $config);
         $visualizations = WidgetRegistry::getAvailableVisualizations($widget);
         $options = WidgetRegistry::getWidgetOptions($config);
         $advanced_options = WidgetRegistry::supportsAdvancedVisualOptions($widget);
         $limit = max(1, min(50, (int) ($options['limit'] ?? 10)));
         $show_labels = (int) ($options['show_labels'] ?? 1) === 1;
         $gradient = (int) ($options['gradient'] ?? 0) === 1;
         $color = (string) ($options['color'] ?? '');
         $background = (string) ($options['background'] ?? '');

         echo "<div class='dashboardplus-widget-config-row'>";
         echo "<label class='dashboardplus-switch'>";
         echo "<input type='checkbox' name='enabled_widgets[" . Html::cleanInputText($key) . "]' value='1'" . ($enabled ? ' checked' : '') . ">";
         echo "<span></span>";
         echo "</label>";
         echo "<div class='dashboardplus-widget-config-title'>";
         echo "<strong><i class='" . Html::cleanInputText($widget->getIcon()) . "'></i> " . Html::cleanInputText($widget->getTitle()) . "</strong>";
         echo "<small>" . Html::cleanInputText($widget->getDescription()) . "</small>";
         echo "</div>";
         echo "<label class='dashboardplus-visualization'>";
         echo "<span>" . __('Visualização', 'dashboardplus') . "</span>";
         echo "<select class='form-select' name='widget_visualization[" . Html::cleanInputText($key) . "]'>";
         foreach ($visualizations as $value => $label) {
            $selected = $value === $visualization ? ' selected' : '';
            echo "<option value='" . Html::cleanInputText($value) . "'{$selected}>" . Html::cleanInputText($label) . "</option>";
         }
         echo "</select>";
         echo "</label>";
         echo "<label class='dashboardplus-order'>";
         echo "<span>" . __('Ordem', 'dashboardplus') . "</span>";
         echo "<input class='form-control' type='number' min='0' max='999' name='widget_order[" . Html::cleanInputText($key) . "]' value='{$order}'>";
         echo "</label>";
         echo "<label class='dashboardplus-size'>";
         echo "<span>" . __('Largura', 'dashboardplus') . "</span>";
         echo "<select class='form-select' name='widget_width[" . Html::cleanInputText($key) . "]'>";
         foreach ([1, 2, 3, 4, 6, 8, 9, 12] as $candidate) {
            $selected = $candidate === $width ? ' selected' : '';
            echo "<option value='{$candidate}'{$selected}>{$candidate}/12</option>";
         }
         echo "</select>";
         echo "</label>";
         echo "<label class='dashboardplus-size'>";
         echo "<span>" . __('Altura', 'dashboardplus') . "</span>";
         echo "<select class='form-select' name='widget_height[" . Html::cleanInputText($key) . "]'>";
         foreach ([1 => __('Pequena', 'dashboardplus'), 2 => __('Média', 'dashboardplus'), 3 => __('Alta', 'dashboardplus'), 4 => __('Grande', 'dashboardplus'), 5 => __('Extra', 'dashboardplus'), 6 => __('Máxima', 'dashboardplus'), 8 => __('Panorâmica', 'dashboardplus')] as $candidate => $label) {
            $selected = $candidate === $height ? ' selected' : '';
            echo "<option value='{$candidate}'{$selected}>" . Html::cleanInputText($label) . "</option>";
         }
         echo "</select>";
         echo "</label>";
         if ($advanced_options) {
            echo "<div class='dashboardplus-widget-advanced'>";
            echo "<label>";
            echo "<span>" . __('Limite', 'dashboardplus') . "</span>";
            echo "<input class='form-control' type='number' min='1' max='50' name='widget_limit[" . Html::cleanInputText($key) . "]' value='{$limit}'>";
            echo "</label>";
            echo "<label class='dashboardplus-inline-option'>";
            echo "<input type='checkbox' name='widget_show_labels[" . Html::cleanInputText($key) . "]' value='1'" . ($show_labels ? ' checked' : '') . ">";
            echo "<span>" . __('Mostrar valores', 'dashboardplus') . "</span>";
            echo "</label>";
            echo "<label class='dashboardplus-inline-option'>";
            echo "<input type='checkbox' name='widget_gradient[" . Html::cleanInputText($key) . "]' value='1'" . ($gradient ? ' checked' : '') . ">";
            echo "<span>" . __('Gradiente', 'dashboardplus') . "</span>";
            echo "</label>";
            echo "<label>";
            echo "<span>" . __('Cor', 'dashboardplus') . "</span>";
            echo "<input class='form-control' type='text' name='widget_color[" . Html::cleanInputText($key) . "]' value='" . Html::cleanInputText($color) . "' placeholder='#2563eb'>";
            echo "</label>";
            echo "<label>";
            echo "<span>" . __('Fundo', 'dashboardplus') . "</span>";
            echo "<input class='form-control' type='text' name='widget_background[" . Html::cleanInputText($key) . "]' value='" . Html::cleanInputText($background) . "' placeholder='#fafafa'>";
            echo "</label>";
            echo "</div>";
         }
         echo "</div>";
      }
      echo "</div>";
      echo "</section>";

      echo "<div class='center mt-3'>";
      echo Html::submit(__('Salvar', 'dashboardplus'), [
         'name'  => 'save_dashboardplus_config',
         'class' => 'btn btn-primary',
         'icon'  => 'ti ti-device-floppy',
      ]);
      echo "</div>";

      Html::closeForm();
      echo "</div>";
   }

   private static function numberField(string $name, string $label, int $value, int $min, int $max): void
   {
      echo "<label>";
      echo "<span>" . Html::cleanInputText($label) . "</span>";
      echo "<input class='form-control' type='number' min='{$min}' max='{$max}' name='{$name}' value='{$value}'>";
      echo "</label>";
   }

   private static function switchField(string $name, string $label, int $value): void
   {
      echo "<label class='dashboardplus-inline-switch'>";
      echo "<span>" . Html::cleanInputText($label) . "</span>";
      echo "<input type='hidden' name='{$name}' value='0'>";
      echo "<input type='checkbox' name='{$name}' value='1'" . ($value === 1 ? ' checked' : '') . ">";
      echo "</label>";
   }

   private static function selectField(string $name, string $label, string $value, array $options): void
   {
      echo "<label>";
      echo "<span>" . Html::cleanInputText($label) . "</span>";
      echo "<select class='form-select' name='{$name}'>";
      foreach ($options as $key => $option_label) {
         $selected = $key === $value ? ' selected' : '';
         echo "<option value='" . Html::cleanInputText((string) $key) . "'{$selected}>"
            . Html::cleanInputText((string) $option_label)
            . "</option>";
      }
      echo "</select>";
      echo "</label>";
   }

   private static function colorField(string $name, string $label, string $value): void
   {
      $safe_value = Html::cleanInputText($value);
      echo "<label class='dashboardplus-color-field'>";
      echo "<span>" . Html::cleanInputText($label) . "</span>";
      echo "<span>";
      echo "<input type='color' value='{$safe_value}' data-dashboardplus-color-picker>";
      echo "<input class='form-control' type='text' name='{$name}' value='{$safe_value}' placeholder='#10b6b4'>";
      echo "</span>";
      echo "</label>";
   }

   private static function tabsCheckboxes(string $name, array $selected): void
   {
      $selected = Config::normalizeEnabledTabs($selected);
      echo "<div class='dashboardplus-tab-checkboxes'>";
      foreach (Config::getDashboardLabels() as $key => $label) {
         $checked = in_array($key, $selected, true) ? ' checked' : '';
         echo "<label class='dashboardplus-inline-option'>";
         echo "<input type='checkbox' name='" . Html::cleanInputText($name) . "[]' value='" . Html::cleanInputText($key) . "'{$checked}>";
         echo "<span>" . Html::cleanInputText($label) . "</span>";
         echo "</label>";
      }
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
