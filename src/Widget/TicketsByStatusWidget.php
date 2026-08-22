<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByStatusWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_status';
   }

   public function getTitle(): string
   {
      return __('Chamados por status', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-list-check';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByStatus($context);
   }
}
