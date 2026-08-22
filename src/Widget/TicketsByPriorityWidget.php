<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByPriorityWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_priority';
   }

   public function getTitle(): string
   {
      return __('Chamados por prioridade', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-flag';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByPriority($context);
   }
}
