<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionGeneralBreakdownWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_general_breakdown';
   }

   public function getTitle(): string
   {
      return __('Satisfação geral', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-donut';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionGeneralBreakdown($context);
   }
}
