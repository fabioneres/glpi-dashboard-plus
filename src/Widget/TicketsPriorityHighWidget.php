<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsPriorityHighWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_priority_high';
   }

   public function getTitle(): string
   {
      return __('Prioridade alta', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com prioridade alta ou muito alta', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-flag-2';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByPriorities($context, [4, 5]);
   }
}
