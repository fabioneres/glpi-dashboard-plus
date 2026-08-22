<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TaskEffortByTechnicianWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'task_effort_by_technician';
   }

   public function getTitle(): string
   {
      return __('Horas de tarefas por técnico', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-list-check';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->taskEffortByTechnician($context);
   }
}
