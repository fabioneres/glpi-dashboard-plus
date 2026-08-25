<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class AverageElapsedSolveTimeClosedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'average_elapsed_solve_time_closed';
   }

   public function getTitle(): string
   {
      return __('Tempo médio de solução', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados fechados, tempo corrido entre abertura e solução', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-clock-hour-8';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->averageElapsedSolveTimeClosed($context);
   }
}
