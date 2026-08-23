<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsPriorityMediumWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_priority_medium';
   }

   public function getTitle(): string
   {
      return __('Prioridade média', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com prioridade média', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-flag';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByPriority($context, 3);
   }
}
