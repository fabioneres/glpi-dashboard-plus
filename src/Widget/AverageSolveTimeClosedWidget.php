<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class AverageSolveTimeClosedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'average_solve_time_closed';
   }

   public function getTitle(): string
   {
      return __('Tempo médio de solução', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados fechados, em horas GLPI/SLA', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-clock-hour-4';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->averageSolveTimeClosed($context);
   }
}
