<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionByGroupAverageWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_by_group_average';
   }

   public function getTitle(): string
   {
      return __('Média de satisfação por grupo', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-users-group';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionByGroupAverage($context);
   }
}
