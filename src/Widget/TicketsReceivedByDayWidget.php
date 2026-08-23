<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsReceivedByDayWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_received_by_day';
   }

   public function getTitle(): string
   {
      return __('Tickets recebidos por dia', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-area-line';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 6,
         'height' => 4,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->receivedByDay($context);
   }
}
