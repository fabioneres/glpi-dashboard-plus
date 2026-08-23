<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\Provider\CapacityMetricsProvider;

abstract class CapacityWidget extends AbstractWidget
{
   protected function provider(): CapacityMetricsProvider
   {
      return new CapacityMetricsProvider();
   }

   public function getIcon(): string
   {
      return 'ti ti-users-group';
   }

   public function getDescription(): string
   {
      return __('Carga operacional estimada sem converter pontos em horas.', 'dashboardplus');
   }
}
