<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class CapacityTechnicianLoadWidget extends CapacityWidget
{
   public function getKey(): string
   {
      return 'capacity_technician_load';
   }

   public function getTitle(): string
   {
      return __('Carga operacional por técnico', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'breakdown';
   }

   public function getDefaultSize(): array
   {
      return ['width' => 6, 'height' => 4];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->technicianLoad($context);
   }
}
