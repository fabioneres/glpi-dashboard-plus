<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsSolvedTodayWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_solved_today';
   }

   public function getTitle(): string
   {
      return __('Solucionados hoje', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados solucionados na data atual', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-calendar-check';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countSolvedToday($context);
   }
}
