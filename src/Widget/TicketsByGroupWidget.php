<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByGroupWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_group';
   }

   public function getTitle(): string
   {
      return __('Chamados por grupo técnico', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-users-group';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByGroup($context);
   }
}
