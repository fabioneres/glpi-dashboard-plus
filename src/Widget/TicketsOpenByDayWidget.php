<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsOpenByDayWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_open_by_day';
   }

   public function getTitle(): string
   {
      return __('Nº de tickets em aberto por dia', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-bar';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 12,
         'height' => 4,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->openByDay($context, 30);
   }
}
