<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByTechnicianWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_technician';
   }

   public function getTitle(): string
   {
      return __('Chamados por técnico', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-user-cog';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByTechnician($context);
   }
}
