<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class CapacityTechnicianLoadTableWidget extends CapacityWidget
{
   public function getKey(): string
   {
      return 'capacity_technician_load_table';
   }

   public function getTitle(): string
   {
      return __('Tabela de apoio da capacidade', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'table';
   }

   public function getDefaultSize(): array
   {
      return ['width' => 12, 'height' => 4];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->technicianLoadTable($context);
   }
}
