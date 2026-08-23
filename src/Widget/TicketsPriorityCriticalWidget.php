<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsPriorityCriticalWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_priority_critical';
   }

   public function getTitle(): string
   {
      return __('Prioridade crítica', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com prioridade crítica', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-alert-triangle';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByPriority($context, 6);
   }
}
