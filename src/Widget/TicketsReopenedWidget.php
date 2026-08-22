<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsReopenedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_reopened';
   }

   public function getTitle(): string
   {
      return __('Chamados reabertos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com solução recusada no período', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-refresh-alert';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countReopened($context);
   }
}
