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

      echo "<div class='dashboardplus-config'>";
      echo "<form method='post' action='" . Config::pluginUrl('/front/config.form.php') . "'>";

      echo "<div class='dashboardplus-config-header'>";
      echo "<h1><i class='" . Config::getIcon() . "'></i> " . __('Configurações do Dashboard Plus', 'dashboardplus') . "</h1>";
      echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/dashboard.php') . "'>";
      echo "<i class='ti ti-arrow-left'></i> " . __('Voltar ao painel', 'dashboardplus');
      echo "</a>";
      echo "</div>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Comportamento geral', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-settings-grid'>";
      self::numberField('default_period_days', __('Período padrão em dias', 'dashboardplus'), (int) $settings['default_period_days'], 1, 366);
      self::numberField('refresh_interval', __('Intervalo de atualização automática em segundos', 'dashboardplus'), (int) $settings['refresh_interval'], 30, 3600);
      self::numberField('cache_ttl', __('Tempo de cache dos widgets em segundos', 'dashboardplus'), (int) $settings['cache_ttl'], 30, 3600);
      self::switchField('auto_refresh', __('Habilitar atualização automática', 'dashboardplus'), (int) $settings['auto_refresh']);
      self::switchField('use_cache', __('Habilitar cache dos widgets', 'dashboardplus'), (int) $settings['use_cache']);
      echo "</div>";
      echo "</section>";

      echo "<section class='dashboardplus-config-section'>";
      echo "<h2>" . __('Escopo de entidades', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-entity-scope'>";
      echo "<label>";
      echo "<span>" . __('Entidades consideradas', 'dashboardplus') . "</span>";
      Dropdown::show(Entity::class, [
         'name'                 => 'dashboardplus_entities_id[]',
         'value'                => $entity_ids,
         'multiple'             => true,
         'width'                => '100%',
         'display_emptychoice'  => false,
         'addicon'              => false,
         'comments'             => false,
      ]);
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
      echo "<h2>" . __('Widgets', 'dashboardplus') . "</h2>";
      echo "<div class='dashboardplus-widget-config-list'>";
      foreach ($widgets as $key => $widget) {
         $config = $widget_configs[$key] ?? WidgetRegistry::getWidgetConfig($key);
         $enabled = (int) ($config['is_enabled'] ?? 0) === 1;
         $order = (int) ($config['display_order'] ?? 999);
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
}
