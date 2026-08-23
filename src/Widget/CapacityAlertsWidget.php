<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class CapacityAlertsWidget extends CapacityWidget
{
   public function getKey(): string
   {
      return 'capacity_alerts';
   }

   public function getTitle(): string
   {
      return __('Alertas de capacidade', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-alert-triangle';
   }

   public function getType(): string
   {
      return 'table';
   }

   public function getDefaultSize(): array
   {
      return ['width' => 12, 'height' => 3];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->alerts($context);
   }
}
