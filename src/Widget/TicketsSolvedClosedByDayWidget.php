<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsSolvedClosedByDayWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_solved_closed_by_day';
   }

   public function getTitle(): string
   {
      return __('Tickets solucionados / encerrados por dia', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-area';
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
      return $this->provider()->solvedClosedByDay($context);
   }
}
