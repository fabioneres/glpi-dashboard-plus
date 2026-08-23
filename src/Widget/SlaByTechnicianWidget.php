<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SlaByTechnicianWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'sla_by_technician';
   }

   public function getTitle(): string
   {
      return __('SLA por técnico', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-user-check';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 12,
         'height' => 5,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->slaComplianceByTechnician($context);
   }
}
