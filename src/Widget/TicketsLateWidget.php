<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsLateWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_late';
   }

   public function getTitle(): string
   {
      return __('Chamados atrasados', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('SLA ou OLA excedido', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-alarm';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countLate($context);
   }
}
